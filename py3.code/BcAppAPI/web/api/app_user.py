from fastapi import APIRouter, Depends
from fastapi.responses import RedirectResponse
from loguru import logger
import json
import time
from io import BytesIO
from utils.exceptions.customs import InvalidPermissions, UnauthorizedAPIRequest, RecordNotFound
from utils.services.base.base_func import *
from utils.services.redis_db_connect.connect import *
from web.models.appuser_models import *

# logger.add(sink='logs/app_user.log',
#            level='ERROR',
#            # colorize=True,     # 设置颜色
#            format='{time:YYYY-MM-DD HH:mm:ss} - {level} - {file} - {line} - {message}',
#            enqueue=True,
#            # serialize=True,    # 序列化为json
#            backtrace=True,   # 设置为'False'可以保证生产中不泄露信息
#            diagnose=True,    # 设置为'False'可以保证生产中不泄露信息
#            rotation='00:00',
#            retention='7 days')

router = APIRouter(dependencies=[Depends(antx_auth)])

user_db = db_connection('bc-app', 'users')
user_info_db = db_connection('bc-app', 'user-info')
promo_db = db_connection('bc-app', 'promo_qrcode')
dnk_db = db_connection('bc-app', 'dnetworks')
miner_db = db_connection('bc-app', 'miners')
asset_db = db_connection('bc-app', 'assets')
miner_pic_db = db_connection('bc-app', 'miner_pics')
record_db = db_connection('bc-app', 'records')
share_buy_db = db_connection('bc-app', 'share_buy_code')
redis_service = redis_connection(redis_db=0)

@logger.catch(level='ERROR')
@router.get('/all')
async def get_all_users():
	pass

@logger.catch(level='ERROR')
@router.post('/add_user')
async def add_user(request: Request, add_info: AddUser):
	pass

@logger.catch(level='ERROR')
@router.get('/{user_id}')
async def get_user(user_id):
	user_info = user_db.find_one({'user_id': user_id})
	promo_invite_info = user_info_db.find_one({'user_id': user_id})
	return_info = {
		'user_id': user_id,
		'nickname': user_info['nickname'],
		'phone': user_info['phone'],
		'email': user_info['email'],
		'invite_code': promo_invite_info['share']['invite_code'],
		'promo_code': user_info['share']['promo_code'],
		'register_time': user_info['created_time'],
		'last_login_time': user_info['last_login_time'],
		'last_login_ip': user_info['last_login_ip'],
		'is_logged_in': user_info['is_logged_in'],
		'is_verified': user_info['is_verified'],
		'level_status': '',
		'member_status': ''
	}
	return msg(status='success', data=return_info)

@logger.catch(level='ERROR')
@router.post('/update_user')
async def update_user():
	pass

@logger.catch(level='ERROR')
@router.post('/delete_user')
async def delete_user():
	pass
