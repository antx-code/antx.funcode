from fastapi import APIRouter, Depends
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
share_buy_db = db_connection('bc-app', 'share_buy_code')
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
@router.post('/share_buy')
async def share_buy(request: Request, share_buy: ShareBuy):
	user_id = antx_auth(request)
	share_code, share_url = generate_share_code_url()
	redis_share_info = {
		'team_header': user_id,
		'members': [user_id],  # 包括团长
		'member_count': 1,
		'team_buy_number': CONFIG['TeamBuyNumber'],
		'miner_name': share_buy.miner_name
	}
	now_time = time.strftime('%Y-%m-%d %H:%M:%S', time.localtime(time.time()))
	db_share_info = {
		'team_header': user_id,
		'created_time': now_time,
		'update_time': '',
		'members': [],
		'member_count': 0,
		'team_buy_number': CONFIG['TeamBuyNumber'],
		'miner_name': share_buy.miner_name,
		'status': 'Created'
	}

	redis = redis_connection(redis_db=1)
	redis.set_dep_key(key_name=share_code, key_value=json.dumps(redis_share_info, ensure_ascii=False), expire_secs=1800)
	share_buy_db.insert_one_data({'share_code': share_code, 'share_info': db_share_info})
	return msg(status='success', data={'share_code': share_code, 'share_url': share_url})

@logger.catch(level='ERROR')
@router.get('/get_share_code')
async def get_share_code(request: Request):
	user_id = antx_auth(request)
	share_codes = []
	results = []
	redis = redis_connection(redis_db=1)
	share_infos = share_buy_db.query_data({'share_info.team_header': user_id})
	for share_info in share_infos:
		share_codes.append(share_info['share_code'])
	logger.info(share_codes)
	for code in share_codes:
		expires = redis.redis_client.ttl(name=code)
		if expires == -2:
			results.append({'share_code': code, 'status': 'Share buy url was expired!'})
		else:
			results.append({'share_code': code, 'status': 'Active'})
	return msg(status='success', data=results)


@logger.catch(level='ERROR')
@router.get('/share/{share_code}')
async def share_buy_code(request: Request, share_code):
	user_id = antx_auth(request)
	redis = redis_connection(redis_db=1)
	expires = redis.redis_client.ttl(name=share_code)
	db_share_info = share_buy_db.find_one({'share_code': share_code})['share_info']
	if expires == -2:
		now_time = time.strftime('%Y-%m-%d %H:%M:%S', time.localtime(time.time()))
		db_share_info['update_time'] = now_time
		db_share_info['status'] = 'Cannel'
		share_buy_db.update_one({'share_code': share_code}, {'share_info': db_share_info})
		refund = miner_db.find_one({'miner_name': db_share_info['miner_name']})['miner_team_price']
		refund_money(share_code, round((refund/CONFIG['TeamBuyNumber']), 2))
		return msg(status='error', data='Share buy url was expired!', code=212)
	redis_share_info = redis.get_key_expire_content(key_name=share_code)
	redis_share_info = json.loads(redis_share_info)
	redis_share_info['members'].append(user_id)
	share_buy_count = redis_share_info['member_count']
	if share_buy_count > CONFIG['TeamBuyNumber']:
		return msg(status='error', data='Number of buyers exceeded!', code=211)
	redis_share_info['member_count'] += 1
	redis.set_dep_key(key_name=share_code, key_value=json.dumps(redis_share_info, ensure_ascii=False), expire_secs=expires)
	now_time = time.strftime('%Y-%m-%d %H:%M:%S', time.localtime(time.time()))
	db_share_info['update_time'] = now_time
	db_share_info['members'].append(user_id)
	db_share_info['member_count'] += 1
	db_share_info['status'] = 'Active'
	share_buy_db.update_one({'share_code': share_code}, {'share_info': db_share_info})
	return msg(status='success', data='Click success')

@logger.catch(level='ERROR')
@router.get('/share_monitor/{share_code}')
async def share_monitor(request: Request, share_code):
	redis = redis_connection(redis_db=1)
	expires = redis.redis_client.ttl(name=share_code)
	db_share_info = share_buy_db.find_one({'share_code': share_code})['share_info']
	if expires == -2:
		now_time = time.strftime('%Y-%m-%d %H:%M:%S', time.localtime(time.time()))
		db_share_info['update_time'] = now_time
		db_share_info['status'] = 'Cannel'
		share_buy_db.update_one({'share_code': share_code}, {'share_info': db_share_info})
		refund = miner_db.find_one({'miner_name': db_share_info['miner_name']})['miner_team_price']
		refund_money(share_code, round((refund / CONFIG['TeamBuyNumber']), 2))
		return msg(status='error', data='Share buy url was expired!', code=212)
	redis_share_info = redis.get_key_expire_content(key_name=share_code)
	redis_share_info = json.loads(redis_share_info)
	share_buy_count = redis_share_info['member_count']
	if share_buy_count > CONFIG['TeamBuyNumber']:
		return msg(status='error', data='Number of buyers exceeded!', code=211)
	elif share_buy_count ==CONFIG['TeamBuyNumber']:
		return msg(status='success', data='Congratulations, team share buy number is full!')
	else:
		return msg(status='success', data='Wating for more team share buy member!')

def refund_money(share_buy_code, miner_per_price):
	share_buy_info = share_buy_db.find_one({'share_code': share_buy_code})['share_info']
	members = share_buy_info['members']
	# member_count = share_buy_info['member_count']
	for member in members:
		logger.info(member)
		user_asset = asset_db.find_one({'user_id': member})['asset']
		user_asset_all = user_asset['usdt']['all']
		logger.info(user_asset_all)
		logger.info(user_asset_all + miner_per_price)
		asset_db.update_one({'user_id': member}, {'asset.usdt.all': user_asset_all + miner_per_price})

@logger.catch(level='ERROR')
@router.post('/team_share_buy')
async def team_share_buy_miner(request: Request, buy_info: TeamBuyMiner):
	user_id = antx_auth(request)
	user_asset = asset_db.find_one({'user_id': user_id})['asset']
	redis = redis_connection(redis_db=1)
	share_buy_info = redis.get_key_expire_content(buy_info.share_buy_code)
	share_buy_info = json.loads(share_buy_info)
	members = share_buy_info['members']
	member_count = share_buy_info['member_count']
	if user_asset['usdt']['all'] < buy_info.miner_per_price:
		return msg(status='error', data='Order created failed, your wallet balance is not enough to buy, please recharge!', code=209)

	miner_id = generate_miner_id()
	now_time = time.strftime('%Y-%m-%d %H:%M:%S', time.localtime(time.time()))
	# per_pay_money = round((buy_info.miner_sum_price / CONFIG['TeamBuyNumber']), 2)
	return_info = {
		'miner_id': miner_id,
		'miner_name': buy_info.miner_name,
		'pay_money': buy_info.miner_per_price,
		'wallet_balance': user_asset['usdt']['all'] - buy_info.miner_per_price,
		'order_created_time': now_time
	}
	asset_miner = {
		'miner_id': miner_id,
		'miner_name': buy_info.miner_name,
		'created_time': now_time,
		'update_time': '',
		'alive_time': '00:00:00',
		'members': members,  # nickname
		'member_count': member_count,
		'share_code': buy_info.share_buy_code,
		'all': 0,
		'today_rewards': 0, # 今日总收益
		'today_reward': 0   # 今日个人收益 = 今日总收益 / 团队人数
	}
	asset_db.update_one({'user_id': user_id}, {'asset.usdt.all': user_asset['usdt']['all'] - buy_info.miner_per_price})
	asset_db.collection.update_one({'user_id': user_id}, {'$push': {'asset.team_miner': asset_miner}}, upsert=True)
	record_db.insert_one_data(record_buy(user_id, buy_info.miner_name, miner_id, buy_info.miner_per_price, buy_type='team'))

	miner_info = miner_db.find_one({'miner_name': buy_info.miner_name})
	miner_numbers = miner_info['miner_numbers']
	miner_db.update_one({'miner_name': buy_info.miner_name}, {'miner_numbers': miner_numbers - 1})
	return msg(status='success', data=return_info)

@logger.catch(level='ERROR')
@router.post('/recharge')
async def recharge(request: Request, recharge_info: RechargeInfo):
	user_id = antx_auth(request)
	asset = asset_db.find_one({'user_id': user_id})['asset']['usdt']['all']
	asset_db.update_one({'user_id': user_id}, {'asset.usdt.all': asset + recharge_info.recharge_usdt})
	record_db.insert_one_data(record_recharge_withdraw(user_id, 'recharge', recharge_info.recharge_usdt))
	return msg(status='success', data="Recharge request success, please waiting for process!")

@logger.catch(level='ERROR')
@router.post('/withdraw')
async def withdraw(request: Request, withdraw_info: WithdrawInfo):
	user_id = antx_auth(request)
	asset = asset_db.find_one({'user_id': user_id})['asset']['usdt']['all']
	asset_db.update_one({'user_id': user_id}, {'asset.usdt.all': asset - withdraw_info.withdraw_usdt})
	record_db.insert_one_data(record_recharge_withdraw(user_id, 'withdraw', withdraw_info.withdraw_usdt))
	return msg(status='success', data="Withdraw request success, please waiting for process!")

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

