from fastapi import APIRouter, Depends, BackgroundTasks, Request, UploadFile, File
from loguru import logger
from utils.services.base.base_func import *
from app.handler.miner_handler import *
from utils.services.redis_db_connect.connect import *
from app.models.miner_models import *

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
miner_db = db_connection('bc-app', 'miner')
redis_service = redis_connection(redis_db=0)

CONFIG = redis_service.hget_redis(redis_key='config', content_key='app')


@logger.catch(level='ERROR')
@router.get('/rewards')
async def get_team_members(request: Request, dep=Depends(antx_auth), ressponse_model=MinerReward):
	user_id = dep
	miner_reward = {
		'miner_id': '1234',
		'created_time': '2021-01-01 21:20:12',
		'sum_rewards': 0.0003,
		'alive_time': '23:11:21'
	}
	return miner_reward

@logger.catch(level='ERROR')
@router.get('/team_rewards')
async def get_team_members(request: Request, dep=Depends(antx_auth)):
	user_id = dep
	return TeamMineReward
