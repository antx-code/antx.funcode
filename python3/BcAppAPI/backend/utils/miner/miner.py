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
		self.miner_exceptd_reward = config_info['MinerReward']
		self.level1 = config_info['Level1Reward']
		self.level2 = config_info['Level2Reward']
		self.level3 = config_info['Level3Reward']
		self.manage_fee = config_info['MinerManageFee']

	@logger.catch(level='ERROR')
	def miner_running(self):
		pass

	@logger.catch(level='ERROR')
	def team_miner_running(self):
		pass

	@logger.catch(level='ERROR')
	def miner_status_manager(self):
		pass

	@logger.catch(level='ERROR')
	def dia(self):
		now_time = int(time.time())
		today = str(datetime.today()).split(' ')[0]
		today_timestamp = format2timestamp(f'{today} 00:00:00')


	@logger.catch(level='ERROR')
	def test(self):
		logger.info(self.level1)

if __name__ == '__main__':
    miner_reward_runner = MinerRewardRunner()
    # miner_reward_runner.dia()
    miner_reward_runner.test()
