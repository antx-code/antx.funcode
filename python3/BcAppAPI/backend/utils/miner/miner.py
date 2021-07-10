import time
from datetime import datetime
from loguru import logger
from utils.services.redis_db_connect.connect import *

asset_db = db_connection('bc-app', 'assets')
miner_reward_record_db = db_connection('bc-app', 'miner_reward_record')
dnk_db = db_connection('bc-app', 'dnetworks')
redis_service = redis_connection(redis_db=0)

# 每个人的充值的总资产 asset -> usdt -> all
# 每个人的总收益 asset -> usdt -> sum_reward
# 每个人的今日总收益 asset -> usdt -> today_reward
# 每个矿机的总收益 -> asset -> miner -> all
# 每个人的分级收益 -> asset -> share
# 每个人的总资产 = 每个人的充值的总资产 + 每个人的总收益 + 每个矿机的总收益 + 每个人的分级收益

def format2timestamp(format_time: str):
	timestamp = time.mktime(time.strptime(format_time, "%Y-%m-%d %H:%M:%S"))
	return int(timestamp)

class MinerRewardRunner():
	@logger.catch(level='ERROR')
	def __init__(self):
		config_info = redis_service.hget_redis('config', 'app')
		self.miner_exceptd_reward = config_info['MinerReward']  # 预期日收益
		self.level1 = config_info['Level1Reward']   # 百分比， 上级的上级
		self.level2 = config_info['Level2Reward']   # 百分比， 上级
		self.level3 = config_info['Level3Reward']   # 百分比, 自己
		self.manage_fee = config_info['MinerManageFee'] # 百分比
		self.team_number = config_info['TeamBuyNumber'] # 团购人数

	@logger.catch(level='ERROR')
	def miner_running(self, miner: dict):
		now_time = int(time.time())
		today = str(datetime.today()).split(' ')[0]
		today_timestamp = format2timestamp(f'{today} 00:00:00')
		second_reward = round(self.miner_exceptd_reward / 86400, 5) # 预期每秒收益
		created_time = miner['created_time']
		sum_timestamp = format2timestamp(created_time)
		sum_diff = now_time - sum_timestamp
		today_diff = now_time - today_timestamp
		miner_today_reward = round(today_diff * second_reward * (1-self.manage_fee), 2)   # 此时未按三级分发，已扣除管理费
		miner_all_reward = round(sum_diff * second_reward * (1-self.manage_fee), 2)   # 此时未按三级分发，已扣除管理费
		return miner_today_reward, miner_all_reward

	@logger.catch(level='ERROR')
	def team_miner_running(self, miner: str):
		now_time = int(time.time())
		today = str(datetime.today()).split(' ')[0]
		today_timestamp = format2timestamp(f'{today} 00:00:00')
		second_reward = round(self.miner_exceptd_reward / 86400, 5)  # 预期每秒收益
		created_time = miner['created_time']
		sum_timestamp = format2timestamp(created_time)
		sum_diff = now_time - sum_timestamp
		today_diff = now_time - today_timestamp
		miner_today_rewards = round(today_diff * second_reward * (1 - self.manage_fee), 2)  # 此时未按三级分发，已扣除管理费,今日矿机总收益
		miner_today_reward = round((today_diff * second_reward * (1 - self.manage_fee)) / self.team_number, 2)  # 此时未按三级分发，已扣除管理费，今日矿机总收益个人所得部分
		miner_all_reward = round((sum_diff * second_reward * (1 - self.manage_fee)) / self.team_number, 2)  # 此时未按三级分发，已扣除管理费
		return miner_today_reward, miner_all_reward, miner_today_rewards

	@logger.catch(level='ERROR')
	def usdt_reward(self):
		asset_count = asset_db.collection.count_documents({})
		if (asset_count // 10) == 0:
			pages = (asset_count // 10)
		else:
			pages = (asset_count // 10) + 1
		for i in range(pages):
			assets = asset_db.collection.find({}, {'_id': 0}).skip(i).limit(10)
			for asset in assets:
				user_id = asset['user_id']
				m_reward = 0
				m_today_reward = 0
				for miner in asset['asset']['miner']:
					m_today_reward += miner['today_reward']
					m_reward += miner['all']
				for miner in asset['asset']['team_miner']:
					m_today_reward += miner['today_reward']
					m_reward += miner['all']
				asset_db.update_one({'user_id': user_id}, {'asset.usdt.sum_reward': m_reward, 'asset.usdt.today_reward': m_today_reward})

	@logger.catch(level='ERROR')
	def share_reward(self):
		pass

	@logger.catch(level='ERROR')
	def miner_status_manager(self):
		pass

	@logger.catch(level='ERROR')
	def dia(self):
		asset_count = asset_db.collection.count_documents({})
		if (asset_count // 10) == 0:
			pages = (asset_count // 10)
		else:
			pages = (asset_count // 10) + 1
		for i in range(pages):
			assets = asset_db.collection.find({}, {'_id': 0}).skip(i).limit(10)
			for asset in assets:
				for miner in asset['asset']['miner']:
					logger.info(miner['miner_id'])
					miner_id = miner['miner_id']
					miner_today_reward, miner_all_reward = self.miner_running(miner)

					asset_db.update_one({'asset.miner.miner_id': miner_id},
					                    {'asset.miner.$.today_reward': round(miner_today_reward * self.level3, 2)})
					asset_db.update_one({'asset.miner.miner_id': miner_id},
					                    {'asset.miner.$.all': round(miner_all_reward * self.level3, 2)})
				for miner in asset['asset']['team_miner']:
					miner_id = miner['miner_id']
					miner_today_reward, miner_all_reward, miner_today_rewards = self.team_miner_running(miner)
					asset_db.update_one({'asset.team_miner.miner_id': miner_id},
					                    {'asset.team_miner.$.today_reward': round(miner_today_reward * self.level3, 2)})
					asset_db.update_one({'asset.team_miner.miner_id': miner_id},
					                    {'asset.team_miner.$.all': round(miner_all_reward * self.level3, 2)})
					asset_db.update_one({'asset.team_miner.miner_id': miner_id},
					                    {'asset.team_miner.$.today_rewards': miner_today_rewards})
		self.usdt_reward()
		self.share_reward()

	@logger.catch(level='ERROR')
	def test(self):
		logger.info(self.manage_fee)
		self.usdt_reward()

if __name__ == '__main__':
    miner_reward_runner = MinerRewardRunner()
    # miner_reward_runner.dia()
    miner_reward_runner.test()
