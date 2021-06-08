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
promo_db = db_connection('bc-app', 'promo_qrcode')
redis_service = redis_connection(redis_db=0)

@logger.catch(level='ERROR')
@router.post('/registerlogin')
async def login(user_info: UserRegisterLogin, request: Request):
    users = []
    users.extend(mongodb.dep_data('email'))
    users.extend(mongodb.dep_data('phone'))
    if not user_info.email and not user_info.phone:
        return msg(status='error', data="字段不能为空")

    if user_info.email:
        username = user_info.email
    else:
        if len(user_info.phone) != 11:
            return msg(status='error', data='请输入正确的手机号')
        username = user_info.phone

    if username not in users:
        user_id = id_worker.get_id()   # 生成唯一用户id
        dnetworks(user_id, promo_code(user_id), user_info.invite_code)
    else:
        try:
            user_id = mongodb.find_one({'email': username})['user_id']
        except Exception as e:
            user_id = mongodb.find_one({'phone': username})['user_id']
    now_time = time.strftime('%Y-%m-%d %H:%M:%S', time.localtime(time.time()))
    save_info = {
        'user_id': user_id,
        'nickname': str(username),
        'email': '',
        'phone': '',
        'password': '',
        'created_time': now_time,
        'last_login_time': '',
        'last_login_ip': request.client.host,
        'is_active': True,
        'is_verified': True,
        'is_superuser': False,
        'is_logged_in': True
    }
    if user_info.email:
        save_info['email'] = user_info.email
    else:
        save_info['phone'] = user_info.phone
    mongodb.update_one({'user_id': user_id}, save_info)
    generate_qrcode(user_id, promo_code(user_id))
    access_token = create_authtoken(user_id=user_id, identity='bc-app')['access_token']
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
@router.post('/set_password')
async def forgot_password(user_info: UserSetPassword, dep=Depends(antx_auth)):
    user_id = dep
    is_logged_in = mongodb.find_one({'user_id': user_id})['is_logged_in']
    if not is_logged_in:
        raise InvalidPermissions("账号已登出，请重新登陆")

    if result_hash(user_info.new_password) != result_hash(user_info.new_repassword):
        return msg(status="error", data="两次输入新密码不一致，请检查后重新输入")
    if not user_info.new_password or not user_info.new_repassword:
        return msg(status='error', data='密码不能为空')

    update_info = {"password": result_hash(user_info.new_password)}
    mongodb.update_one({'phone': user_info.phone}, update_info)
    return msg(status='success', data="设置密码成功")

@logger.catch(level='ERROR')
@router.post('/get_verify_code')
async def get_verify_code(verify_info: GetVerifyCode, dep=Depends(antx_auth)):
    if verify_info.verify_type == 'email':
        pass
    else:
        pass
    return msg(status="success", data="验证码已发送，请注意查收")