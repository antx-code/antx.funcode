from loguru import logger
from utils.services.redis_db_connect.connect import *

class MinerRewardRunner():
	@logger.catch(level='ERROR')
	def __init__(self):
		pass

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
		pass

	@logger.catch(level='ERROR')
	def test(self):
		pass

if __name__ == '__main__':
    miner_reward_runner = MinerRewardRunner()
    # miner_reward_runner.dia()
    miner_reward_runner.test()
