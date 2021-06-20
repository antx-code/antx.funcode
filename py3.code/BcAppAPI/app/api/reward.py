from fastapi import APIRouter, Depends, BackgroundTasks, Request, UploadFile, File
from fastapi.responses import RedirectResponse
from loguru import logger
import json
import time
import datetime
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
promo_db = db_connection('bc-app', 'promo_qrcode')
dnk_db = db_connection('bc-app', 'dnetworks')
avatar_db = db_connection('bc-app', 'avatar')
asset_db = db_connection('bc-app', 'assets')
miner_reward_record_db = db_connection('bc-app', 'miner_reward_record')
redis_service = redis_connection(redis_db=0)

def time2seconds(st):
	h, m, s = st.strip().split(":")
	time2sec = int(h) * 3600 + int(m) * 60 + int(s)
	return time2sec

def seconds2time(st):
	m, s = divmod(st, 60)
	h, m = divmod(m, 60)
	return h, m, s

def get_recent7date(n=7):
	dates = []
	today = datetime.datetime.now()
	for i in range(n):
		dates.append((today - datetime.timedelta(days=i)).strftime("%Y-%m-%d"))
	return dates

@logger.catch(level='ERROR')
@router.get('/home_reward')
async def get_home_reward(request: Request):
	user_id = antx_auth(request)
	logger.info(user_id)
	asset_info = asset_db.find_one({'user_id': user_id})['asset']
	running_time = 0
	if len(asset_info['miner']) > 0:
		for rtime in asset_info['miner']:
			running_time += time2seconds(rtime['alive_time'])
	arh, arm, ars = seconds2time(running_time)

	final_records = []
	result = []
	recent = get_recent7date()
	records = miner_reward_record_db.query_data({
		'$and': [{
			'miner_reward_record': {'$gte': recent[-1], '$lt': recent[0]},
			'user_id': user_id,
			'miner_type': 'personal'
		}]
	})
	for record in records:
		result.append({'record_date': record['miner_reward_record'], 'miner_reward': record['miner_reward']})
	for day in recent:
		sum_reward = 0
		for record in result:
			if day == record['record_date']:
				sum_reward += record['miner_reward']
		record_info = {
			'record_date': day,
			'reward': sum_reward
		}
		final_records.append(record_info)

	miner_list = []
	try:
		miners = asset_info['miner']
		for miner in miners:
			miner['miner_type'] = 'personal'
			miner['status'] = 'running'
			del miner['created_time']
			del miner['today_reward']
		miner_list.extend(miners)
	except Exception as e:
		pass
	try:
		team_miners = asset_info['team_miner']
		for miner in team_miners:
			miner['miner_type'] = 'team'
			miner['status'] = 'running'
			del miner['created_time']
			del miner['today_reward']
			del miner['members']
			del miner['miner_member_count']
		miner_list.extend(team_miners)
	except Exception as e:
		pass

	final_result = {
		'reward': {
			'sum_reward': asset_info['usdt']['sum_reward'],
			'today_reward': asset_info['usdt']['today_reward'],
			'running_time': f'{arh:02d}:{arm:02d}:{ars:02d}'
		},
		'reward_trend': final_records,
		'miner_list': miner_list
	}
	return msg(status='success', data=final_result)

@logger.catch(level='ERROR')
@router.get('/abs_reward')
async def get_abs_reward(request: Request):
	user_id = antx_auth(request)
	miner_reward = 0
	team_miner_reward = 0
	asset_info = asset_db.find_one({'user_id': user_id})['asset']
	miner_records = asset_info['miner']
	team_miner_records = asset_info['team_miner']
	for record in miner_records:
		miner_reward += record['all']
	for record in team_miner_records:
		team_miner_reward += record['all']
	reward_info = {
		'sum_reward': asset_info['usdt']['sum_reward'],
		'miner_reward': miner_reward,
		'team_miner_reward': team_miner_reward
	}
	return msg(status='success', data=reward_info)

@logger.catch(level='ERROR')
@router.get('/my_miner')
async def get_my_miner_reward(request: Request):
	user_id = antx_auth(request)
	final_records = []
	asset_info = asset_db.find_one({'user_id': user_id})
	miner_records = asset_info['asset']['miner']
	team_miner_records = asset_info['asset']['team_miner']
	# for record in miner_records:
		# del record['miner_name']
	final_records.extend(miner_records)
	for record in team_miner_records:
		# del record['miner_name']
		del record['member_count']
		# record['members_img'] = []
		# for member in record['members']:
		# 	try:
		# 		member_id = user_db.find_one({'nickname': member})['user_id']
		# 		avatar = avatar_db.find_one({'user_id': member_id})['avatar']
		# 	except Exception as e:
		# 		avatar = avatar_db.find_one({'user_id': 'default'})['img']
		# 	record['members_img'].append(avatar)
	final_records.extend(team_miner_records)
	reward_info = {
		'asset': asset_info['asset']['usdt']['all'],
		'sum_reward': asset_info['asset']['usdt']['sum_reward'],
		'today_reward': asset_info['asset']['usdt']['today_reward'],
		'miner_reward': final_records
	}
	return msg(status='success', data=reward_info)

@logger.catch(level='ERROR')
@router.get('/miner_reward')
async def get_miner_reward(request: Request):
	user_id = antx_auth(request)
	asset_info = asset_db.find_one({'user_id': user_id})
	records = asset_info['asset']['miner']
	for record in records:
		del record['today_reward']
		del record['miner_name']
	reward_info = {'asset': asset_info['asset']['usdt']['all'], 'miner_reward': records}
	return msg(status='success', data=reward_info)

@logger.catch(level='ERROR')
@router.get('/team_miner_reward')
async def get_team_reward(request: Request):
	user_id = antx_auth(request)
	asset_info = asset_db.find_one({'user_id': user_id})
	records = asset_info['asset']['team_miner']
	for record in records:
		del record['today_reward']
		del record['miner_name']
		del record['today_rewards']
		del record['member_count']
		# record['members_img'] = []
		# for member in record['members']:
		# 	try:
		# 		member_id = user_db.find_one({'nickname': member})['user_id']
		# 		avatar = avatar_db.find_one({'user_id': member_id})['avatar']
		# 	except Exception as e:
		# 		avatar = avatar_db.find_one({'user_id': 'default'})['img']
		# 	record['members_img'].append(avatar)

	reward_info = {'asset': asset_info['asset']['usdt']['all'], 'miner_reward': records}
	return msg(status='success', data=reward_info)
