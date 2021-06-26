from pydantic import BaseModel
from typing import Optional

class GetAllMiners(BaseModel):
    page: int
    size: int

class AddMiner(BaseModel):
	miner_name: str
	miner_month_reward: float
	miner_power: str
	miner_price: float
	miner_team_price: float
	miner_manage_price: float
	miner_sum_count: int

class UpdateInfo(BaseModel):
    pass

class UpdateMiner(BaseModel):
    pass

class DeleteMiner(BaseModel):
    pass
