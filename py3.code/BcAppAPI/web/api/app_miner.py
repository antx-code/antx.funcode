from fastapi import APIRouter, Depends, UploadFile, File
from fastapi.responses import RedirectResponse
from loguru import logger
import json
import time
from io import BytesIO
from utils.exceptions.customs import InvalidPermissions, UnauthorizedAPIRequest, RecordNotFound
from utils.services.base.base_func import *
from utils.services.redis_db_connect.connect import *
from utils.services.base.SnowFlake import IdWorker
from web.models.appminer_models import *

# logger.add(sink='logs/app_miner.log',
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
async def get_all_miners():
	pass

@logger.catch(level='ERROR')
@router.get('/{miner_name}')
async def get_one_miner(miner_name, request: Request)):
	config = redis_service.hget_redis('config', 'app')
	miner_sum_count = config.get(miner_name, config['MinerSumCount'])
	miner_info = miner_db.find_one({'miner_name': miner_name})
	miner_pic = miner_pic_db.find_one({'user_id': miner_info['miner_name']})['img']
	miner_info['miner_sum_count'] = miner_sum_count
	miner_info['miner_sale_count'] = miner_sum_count - miner_info['miner_numbers']
	miner_info['img'] = miner_pic
	return msg(status='success', data=miner_info)

@logger.catch(level='ERROR')
@router.post('/add_miner')
async def add_new_miner(request: Request, add_info: AddMiner):
	miner_info = dict(add_info)
	miner_sum_count = miner_info['miner_sum_count']
	del miner_info['miner_sum_count']
	miner_info['miner_numbers'] = miner_sum_count
	miner_db.insert_one_data(miner_info)
	config = redis_service.hget_redis('config', 'app')
	config[add_info.miner_name] = miner_sum_count
	redis_service.hset_redis('config', 'app', json.dumps(config, ensure_ascii=False))
	return msg(status='success', data='Add new miner successfully')

@logger.catch(level='ERROR')
@router.post('/add_miner_pic')
async def add_miner_pic(request: Request, file: UploadFile = File(...)):
	miner_pic = await file.read()
	miner_name = ''
	miner_pic_db.save_img(user_id=miner_name, img=BytesIO(miner_pic))
	return msg(status='success', data='Add miner picture successfully')

@logger.catch(level='ERROR')
@router.post('/update_miner')
async def update_miner():
	pass

@logger.catch(level='ERROR')
@router.post('/delete_miner')
async def delete_miner():
	pass
