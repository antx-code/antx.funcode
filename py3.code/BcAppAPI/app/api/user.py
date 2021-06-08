from fastapi import APIRouter, Depends, BackgroundTasks, Request
from fastapi.responses import RedirectResponse
from loguru import logger
import json
import time
from utils.exceptions.customs import InvalidPermissions, UnauthorizedAPIRequest, RecordNotFound
from utils.services.base.base_func import *
from utils.services.base.SnowFlake import IdWorker
from utils.services.redis_db_connect.connect import *
from app.models.user_models import *
from app.handler.user_handler import *

# logger.add(sink='logs/users_api.log',
#            level='ERROR',
#            # colorize=True,     # 设置颜色
#            format='{time:YYYY-MM-DD HH:mm:ss} - {level} - {file} - {line} - {message}',
#            enqueue=True,
#            # serialize=True,    # 序列化为json
#            backtrace=True,   # 设置为'False'可以保证生产中不泄露信息
#            diagnose=True,    # 设置为'False'可以保证生产中不泄露信息
#            rotation='00:00',
#            retention='7 days')

# router = APIRouter(dependencies=[Depends(antx_auth)])

router = APIRouter()

id_worker = IdWorker(0, 0)
mongodb = db_connection('bc-app', 'users')
redis_service = redis_connection(redis_db=0)

@logger.catch(level='ERROR')
@router.post('/register_email')
async def register_email(user_info: UserRegisterEmail, request: Request):
    # 判断两次密码是否一致
    # 判断email是否已经注册
    # 判断昵称是否已经被占用
    if user_info.password != user_info.repassword:
        return msg(status='error', data="两次密码不一致")
    if user_info.email in mongodb.dep_data('email'):
        return msg(status='error', data="邮箱已注册")
    if user_info.nickname in mongodb.dep_data('nickname'):
        return msg(status='error', data="昵称已被占用")

    now_time = time.strftime('%Y-%m-%d %H:%M:%S', time.localtime(time.time()))
    user_id = id_worker.get_id()   # 生成唯一用户id
    save_info = {
        'user_id': user_id,
        'nickname': user_info.nickname,
        'email': user_info.email,
        'phone': user_info.phone,
        'password': result_hash(user_info.password),
        'invite_code': user_info.invite_code,
        'promo_code': promo_code(user_id),
        'created_time': now_time,
        'last_login_time': '',
        'last_login_ip': request.client.host,
        'access_token': '',
        'is_active': False,
        'is_verified': False,
        'is_superuser': False,
        'is_logged_in': False
    }
    mongodb.insert_one_data(save_info)
    generate_qrcode(user_id, promo_code(user_id))
    return msg(status='success', data=after_register(user_info.email, user_info.nickname, user_id))

@logger.catch(level='ERROR')
@router.post('/register_phone')
async def register_phone(user_info: UserRegisterPhone, request: Request):
    # 判断两次密码是否一致
    # 判断email是否已经注册
    # 判断昵称是否已经被占用
    # 判断验证码是否正确
    if user_info.password != user_info.repassword:
        return msg(status='error', data="两次密码不一致")
    if user_info.phone in mongodb.dep_data('phone'):
        return msg(status='error', data="手机号已被注册")
    if user_info.nickname in mongodb.dep_data('nickname'):
        return msg(status='error', data="昵称已被占用")
    if user_info.verify_code == '':
        return msg(status='error', data="验证码不能为空")
    # if user_info.verify_code != '':
    #     return msg(status='注册失败', data="验证码不正确，请重新获取")

    now_time = time.strftime('%Y-%m-%d %H:%M:%S', time.localtime(time.time()))
    user_id = id_worker.get_id()  # 生成唯一用户id
    save_info = {
        'user_id': user_id,
        'nickname': user_info.nickname,
        'email': '',
        'phone': user_info.phone,
        'password': result_hash(user_info.password),
        'invite_code': user_info.invite_code,
        'promo_code': promo_code(user_id),
        'created_time': now_time,
        'last_login_time': '',
        'last_login_ip': request.client.host,
        'access_token': '',
        'is_active': False,
        'is_verified': False,
        'is_superuser': False,
        'is_logged_in': False
    }
    mongodb.insert_one_data(save_info)
    generate_qrcode(user_id, promo_code(user_id))
    return msg(status='success', data=after_register(user_info.phone, user_info.nickname, user_id))

@logger.catch(level='ERROR')
@router.post('/login')
async def login(user_info: UserLogin, request: Request):
    if user_info.email:
        # if not mongodb.find_one({'email': user_info.email})['is_verified']:
        #     return msg(status="登陆失败", data="账号未进行安全认证")
        #     return RedirectResponse(url='/api/app/user/verify')
        user_id = mongodb.find_one({'email': user_info.email})['user_id']
        update_login_info = {'email': user_info.email}
        if user_info.email in redis_service.read_redis(redis_key='locked_account'):
            return msg(status="error", data="账户已被锁定，请联系客服")
        if user_info.email not in mongodb.dep_data('email'):
            return msg(status='error', data="用户不存在，请先进行注册")
        if result_hash(user_info.password) != mongodb.find_one({'email': user_info.email})['password']:

            failed_login_count = redis_service.redis_client.incr(name=user_id, amount=1)
            redis_service.set_dep_key(key_name=user_id, key_value=failed_login_count, expire_secs=90)
            if failed_login_count == 5:
                redis_service.new_insert_content(redis_key='locked_account', new_content=user_info.email)
                mongodb.update_one({'user_id': user_id}, {'is_active': False})
                return msg(status="error", data="登录次数已超出限制，账户已被锁定")
            return msg(status='error', data=f"密码不正确，请重新输入，还有{5-failed_login_count}次机会")

    elif user_info.phone:
        user_id = mongodb.find_one({'phone': user_info.phone})['user_id']
        update_login_info = {'phone': user_info.phone}
        if result_hash(user_info.password) != mongodb.find_one({'phone': user_info.phone})['password']:

            failed_login_count = redis_service.redis_client.incr(name=user_id, amount=1)
            redis_service.set_dep_key(key_name=user_id, key_value=failed_login_count, expire_secs=90)
            if failed_login_count == 5:
                redis_service.new_insert_content(redis_key='locked_account', new_content=user_info.phone)
                mongodb.update_one({'user_id': user_id}, {'is_active': False})
                return msg(status="error", data="登录次数已超出限制，账户已被锁定")
            return msg(status='error', data=f"密码不正确，请重新输入，还有{5 - failed_login_count}次机会")

    # user_id = mongodb.find_one({'email': user_info.email})['user_id']
    now_time = time.strftime('%Y-%m-%d %H:%M:%S', time.localtime(time.time()))
    access_token = create_authtoken(user_id=user_id, identity='bc-app')['access_token']
    login_info = {'last_login_time:': now_time, 'last_login_ip': request.client.host, 'is_logged_in': True, 'access_token': access_token}
    mongodb.update_one(update_login_info, login_info)
    return msg(status='success', data={'access_token': access_token})

@logger.catch(level='ERROR')
@router.post('/logout')
async def logout(user_info: UserLogout, dep=Depends(antx_auth)):
    is_logged_in = mongodb.find_one({'user_id': dep})['is_logged_in']
    if not is_logged_in:
        raise InvalidPermissions("账号已登出，token失效")
    if user_info.email:
        mongodb.update_one({'email': user_info.email}, {'is_logged_in': False})
        return msg(status='success', data='已退出登录')
    mongodb.update_one({'phone': user_info.phone}, {'is_logged_in': False})
    return msg(status='success', data='已退出登录')

@logger.catch(level='ERROR')
@router.post('/forgot_password')
async def forgot_password(user_info: UserResetPassword, dep=Depends(antx_auth)):
    is_logged_in = mongodb.find_one({'user_id': dep})['is_logged_in']
    if not is_logged_in:
        raise InvalidPermissions("账号已登出，请重新登陆")

    if result_hash(user_info.new_password) != result_hash(user_info.new_repassword):
        return msg(status="error", data="两次输入新密码不一致，请检查后重新输入")

    update_info = {"password": result_hash(user_info.new_password), 'is_logged_in': False}
    mongodb.update_one({'phone': user_info.phone}, update_info)
    user_id = mongodb.find_one({''})
    access_token = create_authtoken(user_id=user_id, identity='bc-app')['access_token']
    return msg(status='success', data={'access_token': access_token})

@logger.catch(level='ERROR')
@router.post('/reset_password')
async def reset_password(user_info: UserResetPassword, dep=Depends(antx_auth)):
    is_logged_in = mongodb.find_one({'user_id': dep})['is_logged_in']
    if not is_logged_in:
        raise InvalidPermissions("账号已登出，请重新登陆")

    if result_hash(user_info.old_password) != mongodb.find_one({'user_id': dep})['password']:
        return msg(status="error", data="旧密码错误，请重新输入")
    if result_hash(user_info.new_password) != result_hash(user_info.new_repassword):
        return msg(status="error", data="两次输入新密码不一致，请检查后重新输入")

    update_info = {"password": result_hash(user_info.new_password), 'is_logged_in': False}
    mongodb.update_one({'phone': user_info.phone}, update_info)
    user_id = mongodb.find_one({''})
    access_token = create_authtoken(user_id=user_id, identity='bc-app')['access_token']
    return msg(status='success', data={'access_token': access_token})

@logger.catch(level='ERROR')
@router.post('/get_verify_code')
async def get_verify_code(verify_info: UserVerify, dep=Depends(antx_auth)):
    if verify_info.verify_type == 'email':
        pass
    else:
        pass
    return msg(status="success", data="验证码已发送，请注意查收")

@logger.catch(level='ERROR')
@router.post('/verify')
async def verify(verify_info: UserVerify, dep=Depends(antx_auth)):
    if verify_info.verify_type == 'email':
        pass
    else:
        pass
    return msg(status="success", data="账号已通过安全认证")
