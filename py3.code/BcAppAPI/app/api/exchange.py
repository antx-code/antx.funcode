from fastapi import APIRouter, Depends, BackgroundTasks, Request, UploadFile, File
from loguru import logger
from utils.services.base.base_func import *
from app.handler.miner_handler import *
from utils.services.redis_db_connect.connect import *
from app.models.miner_models import *
from app.handler.exchange_handler import *

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
miner_db = db_connection('bc-app', 'miners')
asset_db = db_connection('bc-app', 'assets')
redis_service = redis_connection(redis_db=0)

CONFIG = redis_service.hget_redis(redis_key='config', content_key='app')


@logger.catch(level='ERROR')
@router.get('/miners')
async def get_miners():
	miners = []
	result = miner_db.query_data()
	for miner in result:
		del miner['_id']
		miners.append(miner)
	return msg(status='success', data=miners)

@logger.catch(level='ERROR')
@router.post('/buy_miner')
async def buy_miner(request: Request, buy_info: BuyMiner):
	user_id = antx_auth(request)
	asset = asset_db.find_one({'user_id': user_id})['asset']['usdt']['all']
	if asset < buy_info.miner_price:
		return msg(status='error', data='Order created failed, your balance is not enough to buy, please recharge!', code=209)

	miner_id = generate_miner_id()
	miner_info = {
		'user_id': user_id,
		'miner_id': miner_id,
		'miner_name': buy_info.miner_name,
		'miner_reward': buy_info.miner_reward,
		''
	}
	miner_db.update_one({'miner_name': buy_info.miner_name}, {'miner_numbers': minber_numbers - 1})
	asset_db.update_one({'user_id': user_id}, {'asset.usdt.all': asset - buy_info.miner_price})

@logger.catch(level='ERROR')
@router.post('/team_buy_miner')
async def team_buy_miner():
	pass
