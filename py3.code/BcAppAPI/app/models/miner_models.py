from pydantic import BaseModel
from typing import Optional

class AddMiner(BaseModel):  # 矿机添加->大厅和收益页面
    miner_id: str

class MinerReward(BaseModel):   # 矿机添加->大厅和收益页面
	miner_id: str
	created_time: str
	sum_rewards: float
	alive_time: str
	status: Optional[str]='1'

class TeamMineReward(MinerReward):  # 矿机添加->大厅和收益页面
	team_members: list

class BuyMiner(BaseModel):  # 矿机购买页面
	miner_name: str
	excepted_daily_reward: float
	price: int

class TeamBuyMiner(BuyMiner):   # 矿机购买页面
	buyer_number: int
	reward_type: str

class MyMinerList(BaseModel):   # 我的矿机页面
	miner_id: str
	created_time: str
	sum_rewards: float
	today_rward: float

class MyTeamMinerList(MyMinerList): # 我的矿机页面
	team_members: list
	today_all_rewards: float
