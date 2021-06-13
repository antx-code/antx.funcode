from pydantic import BaseModel
from typing import Optional, List

class BuyMiner(BaseModel):
	miner_name: str
	miner_price: int

class TeamBuyMiner(BuyMiner):
	miner_members: List[str] # 包含发起人本身
	miner_member_count: int

class RecordInfo(BaseModel):
	record_type: str
	record_scope: dict

class WithdrawInfo(BaseModel):
	withdraw_usdt: float
