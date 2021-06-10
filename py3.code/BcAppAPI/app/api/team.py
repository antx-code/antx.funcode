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
promo_db = db_connection('bc-app', 'promo_qrcode')
dnk_db = db_connection('bc-app', 'dnetworks')
avatar_db = db_connection('bc-app', 'avatar')
redis_service = redis_connection(redis_db=0)

@logger.catch(level='ERROR')
@router.get('/members')
async def get_team_members(request: Request, dep=Depends(antx_auth)):
	user_id = dep
	members = []
	af_info = dnk_db.find_one({'user_id': user_id})
	af1_codes = af_info['af1_code']
	af2_codes = af_info['af2_code']
	for af1_code in af1_codes:
		af1_user_info = promo_db.find_one({'base_info.share.promo_code': af1_code})
		af1_user_id = af1_user_info['user_id']
		af1_nickname = af1_user_info['base_info']['profile']['nickname']
		af1_avatar = avatar_db.find_one({'user_id': af1_user_id})
		members.append({'nickname': af1_nickname, 'avatar': af1_avatar})

	for af2_code in af2_codes:
		af2_user_info = promo_db.find_one({'base_info.share.promo_code': af2_code})
		af2_user_id = af2_user_info['user_id']
		af2_nickname = af2_user_info['base_info']['profile']['nickname']
		af2_avatar = avatar_db.find_one({'user_id': af2_user_id})
		members.append({'nickname': af2_nickname, 'avatar': af2_avatar})
	return msg(status='success', data=members)

