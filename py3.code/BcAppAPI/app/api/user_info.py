from fastapi import APIRouter, Depends, BackgroundTasks, Request, UploadFile, File
from fastapi.responses import RedirectResponse
from loguru import logger
import json
import time
from io import BytesIO
from utils.exceptions.customs import InvalidPermissions, UnauthorizedAPIRequest, RecordNotFound
from utils.services.base.base_func import *
from utils.services.redis_db_connect.connect import *
from app.models.user_info_models import *

# logger.add(sink='logs/user_info_api.log',
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

# router = APIRouter()

user_info_db = db_connection('bc-app', 'user-info')
user_db = db_connection('bc-app', 'users')
avatar_db = db_connection('bc-app', 'avatar')
promo_qrcode_db = db_connection('bc-app', 'promo_qrcode')
redis_service = redis_connection(redis_db=0)

@logger.catch(level='ERROR')
@router.post('/avatar/upload')
async def avatar_upload(request: Request, file: UploadFile = File(...)):
	user_id = antx_auth(request)
	avatar = await file.read()
	avatar_db.save_img(user_id=user_id, img=BytesIO(avatar), img_name='avatar')
	return msg(status='success', data=f'上传头像成功')


@logger.catch(level='ERROR')
@router.get('/avatar')
async def get_avatar(request: Request):
	user_id = antx_auth(request)
	avatar = avatar_db.find_one({'user_id': user_id})['avatar']
	return msg(status='success', data={'avatar': avatar})

@logger.catch(level='ERROR')
@router.post('/sprofile')
async def setup_profile(request: Request, user_profile: SetupProfile):
	user_id = antx_auth(request)
	user_info = user_db.find_one({'user_id': user_id})

	if not user_profile.nickname:
		nickname = user_info['nickname']
	else:
		nickname = user_profile.nickname
		user_db.update_one({'user_id': user_id}, {'nickname': nickname})

	save_info = {'user_id': user_id, 'base_info':{
		'profile': {
			'nickname': nickname,
			'sex': user_profile.sex,
			'area': user_profile.area,
			'intro': user_profile.intro
		},
		'share':{
			'promo_code': user_info['promo_code'],
		}
	}}
	user_info_db.update_one({'user_id': user_id}, save_info)
	return msg(status="success", data="修改基础信息成功")

@logger.catch(level='ERROR')
@router.get('/gprofile')
async def get_profile(request: Request):
	user_id = antx_auth(request)
	base_info = user_info_db.find_one({'user_id': user_id})['base_info']['profile']
	return msg(status="success", data=base_info)

@logger.catch(level='ERROR')
@router.get('/share')
async def get_promo_code(request: Request):
	user_id = antx_auth(request)
	share_info = promo_qrcode_db.find_one({'user_id': user_id})
	promo_code = share_info['img_content']
	promo_qrcode = share_info['img']
	share_info = {'promo_code': promo_code, 'qrcode':promo_qrcode}
	return msg(status="success", data=share_info)

