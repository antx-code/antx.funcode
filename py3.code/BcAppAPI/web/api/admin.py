from fastapi import APIRouter, Depends
from fastapi.responses import RedirectResponse
from loguru import logger
import json
import time
from io import BytesIO
from utils.exceptions.customs import InvalidPermissions, UnauthorizedAPIRequest, RecordNotFound
from utils.services.base.base_func import *
from utils.services.redis_db_connect.connect import *
from web.models.admin_models import *

# logger.add(sink='logs/admin.log',
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

admin_db = db_connection('bc-web', 'admin_users')
redis_service = redis_connection(redis_db=0)

@logger.catch(level='ERROR')
@router.post('/login')
async def login(request: Request, login_info: AdminLogin):
	admin_username_infos = admin_db.dep_data({'username'})
	admin_info = admin_db.find_one({'username': login_info.username})
	admin_user_id = admin_info['user_id']
	if login_info.username not in admin_username_infos:
		return msg(status='error', data='Username or password was not correct!')
	if result_hash(login_info.password) != admin_info['password']:
		return msg(status='error', data='Username or password was not correct!')
	failed_login_count = redis_service.redis_client.incr(name=admin_user_id, amount=1)
	redis_service.set_dep_key(key_name=admin_user_id, key_value=failed_login_count, expire_secs=90)
	if failed_login_count == 5:
		redis_service.new_insert_content(redis_key='locked_admin_account', new_content=login_info.username)
		admin_db.update_one({'user_id': admin_user_id}, {'is_active': False})
		return msg(status="error", data="The number of login times has exceeded the limit, and the account has been locked")
	return msg(status='error', data=f"Password was incorrect，only {5 - failed_login_count} times to retry!")
	now_time = time.strftime('%Y-%m-%d %H:%M:%S', time.localtime(time.time()))
	access_token = create_authtoken(user_id=admin_user_id, identity='bc-web')['access_token']
	login_info = {'last_login_time': now_time, 'last_login_ip': request.client.host, 'is_logged_in': True, 'access_token': access_token}
	mongodb.update_one(update_login_info, login_info)
	return msg(status='success', data={'access_token': access_token})

@logger.catch(level='ERROR')
@router.post('/logout')
async def loggout(user_id):
	pass

@logger.catch(level='ERROR')
@router.post('/reset_password')
async def reset_password():
	pass

@logger.catch(level='ERROR')
@router.post('/forgot_password')
async def forgot_password():
	pass

@logger.catch(level='ERROR')
@router.post('/add_admin_user')
async def add_admin_user():
	pass

@logger.catch(level='ERROR')
@router.post('/delete_admin_user')
async def delete_admin_user():
	pass
