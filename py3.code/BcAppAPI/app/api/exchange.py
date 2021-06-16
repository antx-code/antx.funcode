from fastapi import APIRouter, Depends, BackgroundTasks, Request, UploadFile, File
from loguru import logger
from utils.services.base.base_func import *
from app.handler.miner_handler import *
from utils.services.redis_db_connect.connect import *
from app.handler.exchange_handler import *
from app.models.exchange_models import *

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
miner_pic_db = db_connection('bc-app', 'miner_pics')
record_db = db_connection('bc-app', 'records')
redis_service = redis_connection(redis_db=0)

CONFIG = redis_service.hget_redis(redis_key='config', content_key='app')


@logger.catch(level='ERROR')
@router.get('/personal_miners')
async def get_personal_miners():
	miners = []
	result = miner_db.query_data()
	miner_pics = get_miner_pic()
	for miner in result:
		del miner['_id']
		del miner['miner_team_price']
		for miner_pic in miner_pics:
			if miner['miner_name'] == miner_pic['user_id']:
				miner['miner_pic'] = miner_pic['img']
				miners.append(miner)
	return msg(status='success', data=miners)

@logger.catch(level='ERROR')
@router.get('/team_miners')
async def get_team_miners():
	miners = []
	result = miner_db.query_data()
	miner_pics = get_miner_pic()
	for miner in result:
		del miner['_id']
		del miner['miner_price']
		for miner_pic in miner_pics:
			if miner['miner_name'] == miner_pic['user_id']:
				miner['miner_pic'] = miner_pic['img']
				miners.append(miner)
	return msg(status='success', data=miners)

@logger.catch(level='ERROR')
@router.get('/miner/{miner_name}')
async def get_one_miner(miner_name):
	miner_info = miner_db.find_one({'miner_name': miner_name})
	miner_pic = get_miner_pic(miner_name)['img']
	del miner_info['miner_team_price']
	miner_info['miner_pic'] = miner_pic
	return msg(status='success', data=miner_info)

@logger.catch(level='ERROR')
@router.get('/team_miner/{miner_name}')
async def get_one_miner(miner_name):
	miner_info = miner_db.find_one({'miner_name': miner_name})
	miner_pic = get_miner_pic(miner_name)['img']
	del miner_info['miner_price']
	miner_info['miner_pic'] = miner_pic
	return msg(status='success', data=miner_info)

@logger.catch(level='ERROR')
def get_miner_pic(miner_name=None):
	if not miner_name:
		all_pics = []
		result = miner_pic_db.query_data()
		for pic in result:
			del pic['_id']
			all_pics.append(pic)
		return all_pics
	else:
		result = miner_pic_db.find_one({'user_id': miner_name})
		return result

@logger.catch(level='ERROR')
@router.post('/buy_miner')
async def buy_miner(request: Request, buy_info: BuyMiner):
	user_id = antx_auth(request)
	asset = asset_db.find_one({'user_id': user_id})['asset']['usdt']['all']
	if asset < buy_info.miner_price:
		return msg(status='error', data='Order created failed, your balance is not enough to buy, please recharge!', code=209)

	miner_info = miner_db.find_one({'miner_name': buy_info.miner_name})
	miner_numbers = miner_info['miner_numbers']

	miner_id = generate_miner_id()

	now_time = time.strftime('%Y-%m-%d %H:%M:%S', time.localtime(time.time()))
	return_info = {
		'miner_id': miner_id,
		'miner_name': buy_info.miner_name,
		'pay_money': buy_info.miner_price,
		'created_time': now_time
	}
	asset_miner = {
		'miner_id': miner_id,
		'miner_name': buy_info.miner_name,
		'created_time': now_time,
		'alive_time': '00:00:00',
		'all': 0,
		'today_reward': 0,
	}
	miner_db.update_one({'miner_name': buy_info.miner_name}, {'miner_numbers': miner_numbers - 1})
	asset_db.update_one({'user_id': user_id}, {'asset.usdt.all': asset - buy_info.miner_price})
	asset_db.collection.update_one({'user_id': user_id}, {'$push': {'asset.miner': asset_miner}}, upsert=True)
	record_db.insert_one_data(record_buy(user_id, buy_info.miner_name, miner_id, buy_info.miner_price, buy_type='personal'))
	return msg(status='success', data=return_info)

@logger.catch(level='ERROR')
@router.post('/team_buy_miner')
async def team_buy_miner(request: Request, buy_info: TeamBuyMiner):
	user_id = antx_auth(request)
	members_id = []
	all_asset = []
	for member in buy_info.miner_members:
		members_id.append(user_info_db.find_one({'base_info.profile.nickname': member})['user_id'])
	logger.info(members_id)
	for member_id in members_id:
		all_asset.append(asset_db.find_one({'user_id': member_id})['asset']['usdt']['all'])
	if min(all_asset) < buy_info.miner_price:
		return msg(status='error', data='Order created failed, team member\'s balance is not enough to buy, please recharge!', code=209)

	miner_id = generate_miner_id()
	now_time = time.strftime('%Y-%m-%d %H:%M:%S', time.localtime(time.time()))
	per_pay_money = round((buy_info.miner_price / buy_info.miner_member_count), 2)
	return_info = {
		'miner_id': miner_id,
		'miner_name': buy_info.miner_name,
		'members': buy_info.miner_members,
		'member_count': buy_info.miner_member_count,
		'pay_money': buy_info.miner_price,
		'per_pay_money': per_pay_money,
		'created_time': now_time
	}
	asset_miner = {
		'miner_id': miner_id,
		'miner_name': buy_info.miner_name,
		'created_time': now_time,
		'alive_time': '00:00:00',
		'members': buy_info.miner_members,  # nickname
		'member_count': buy_info.miner_member_count,
		'all': 0,
		'today_rewards': 0, # 今日总收益
		'today_reward': 0   # 今日个人收益 = 今日总收益 / 团队人数
	}
	for member_id in members_id:
		member_asset = asset_db.find_one({'user_id': member_id})['asset']['usdt']['all']
		asset_db.update_one({'user_id': member_id}, {'asset.usdt.all': member_asset - per_pay_money})
		asset_db.collection.update_one({'user_id': member_id}, {'$push': {'asset.team_miner': asset_miner}}, upsert=True)
		record_db.insert_one_data(record_buy(member_id, buy_info.miner_name, miner_id, per_pay_money, buy_type='team'))

	miner_info = miner_db.find_one({'miner_name': buy_info.miner_name})
	miner_numbers = miner_info['miner_numbers']
	miner_db.update_one({'miner_name': buy_info.miner_name}, {'miner_numbers': miner_numbers - 1})
	return msg(status='success', data=return_info)

@logger.catch(level='ERROR')
@router.post('/recharge')
async def recharge(request: Request):
	user_id = antx_auth(request)
	asset = asset_db.find_one({'user_id': user_id})['asset']['usdt']['all']
	asset_db.update_one({'user_id': user_id}, {'asset.usdt.all': asset + 10000})
	record_db.insert_one_data(record_recharge_withdraw(user_id, 'recharge', 10000))
	return msg(status='success', data="Recharge success, please wait a moment!")

@logger.catch(level='ERROR')
@router.post('/withdraw')
async def withdraw(request: Request, withdraw_info: WithdrawInfo):
	user_id = antx_auth(request)
	asset = asset_db.find_one({'user_id': user_id})['asset']['usdt']['all']
	asset_db.update_one({'user_id': user_id}, {'asset.usdt.all': asset - withdraw_info.withdraw_usdt})
	record_db.insert_one_data(record_recharge_withdraw(user_id, 'withdraw', withdraw_info.withdraw_usdt))
	return msg(status='success', data="Withdraw success, please wait a moment!")

@logger.catch(level='ERROR')
@router.post('/record')
async def get_record(request: Request, record_info: RecordInfo):
	user_id = antx_auth(request)
	final_records = []
	records = record_db.query_data({
		'$and': [{
			'created_time': {'$gte': record_info.record_scope['start'], '$lt': record_info.record_scope['end']},
			'user_id': user_id,
			'type': record_info.record_type
		}]
	})
	for record in records:
		del record['_id']
		final_records.append(record)
	return final_records

