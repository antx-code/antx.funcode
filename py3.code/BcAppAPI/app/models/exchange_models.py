from pydantic import BaseModel, List

class BuyMiner(BaseModel):
	miner_name: str
	miner_price: int

class MinerMembers(BaseModel):
	nickname: List[str]

class TeamBuyMiner(BuyMiner):
	miner_members: MinerMembers
