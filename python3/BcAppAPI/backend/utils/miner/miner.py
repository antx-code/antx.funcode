import time
from datetime import datetime
from loguru import logger
from utils.services.redis_db_connect.connect import *

asset_db = db_connection('bc-app', 'assets')
miner_reward_record_db = db_connection('bc-app', 'miner_reward_record')
dnk_db = db_connection('bc-app', 'dnetworks')
redis_service = redis_connection(redis_db=0)

# miner_reward -> 矿机预计预收益
# miner_reward record
# dnk -> 分级收益
# asset 每个人的收益
# share_pre1 -> 上级收益

# 每个人的总资产 asset -> usdt -> all
# 每个人的总收益 asset -> usdt -> sum_reward
# 每个人的今日总收益 asset -> usdt -> today_reward
# 每个矿机的总收益 -> asset -> miner -> all

def format2timestamp(format_time: str):
	timestamp = time.mktime(time.strptime(format_time, "%Y-%m-%d %H:%M:%S"))
	return int(timestamp)

def timediff(timediff: int):
	m, s = divmod(timediff, 60)
	h, m = divmod(m, 60)
	return s

class MinerRewardRunner():
	@logger.catch(level='ERROR')
	def __init__(self):
		config_info = redis_service.hget_redis('config', 'app')
		self.miner_exceptd_reward = config_info['MinerReward']  # 预期日收益
		self.level1 = config_info['Level1Reward']   # 百分比
		self.level2 = config_info['Level2Reward']   # 百分比
		self.level3 = config_info['Level3Reward']   # 百分比
		self.manage_fee = config_info['MinerManageFee'] # 百分比

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
	def team_miner_running(self):
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
					self.miner_running(miner)
				# self.team_miner_running(asset)


	@logger.catch(level='ERROR')
	def test(self):
		logger.info(self.manage_fee)

if __name__ == '__main__':
    miner_reward_runner = MinerRewardRunner()
    miner_reward_runner.dia()
    # miner_reward_runner.test()
