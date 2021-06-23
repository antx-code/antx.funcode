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
@router.post('/login')

@logger.catch(level='ERROR')
@router.post('/logout')
async def get_uer_info(user_id):
	pass

@logger.catch(level='ERROR')
@router.post('/reset_password')
async def reset_password():
	pass

@logger.catch(level='ERROR')
@router.post('/forgot_password')
async def forgot_password():
	pass