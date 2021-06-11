from pydantic import BaseModel
from typing import Optional, List

class MinerReward(BaseModel):   # 矿机添加->大厅和收益页面
	miner_id: str
	created_time: str
	sum_rewards: float
	alive_time: str
	status: Optional[str]='1'

class TeamMineReward(MinerReward):  # 矿机添加->大厅和收益页面
	team_members: list

class BuyMiner(BaseModel):  # 矿机购买
	miner_name: str
	miner_reward: float
	miner_price: float

class TeamBuyMiner(BuyMiner):   # 矿机购买
	buyer_number: int
	buyer_members: List[str]
	reward_type: str

class MyMinerList(BaseModel):   # 我的矿机
	miner_id: str
	created_time: str
	sum_rewards: float
	today_rward: float

class MyTeamMinerList(MyMinerList): # 我的矿机
	team_members: list
	today_all_rewards: float
