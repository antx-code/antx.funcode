from pydantic import BaseModel
from typing import Optional
from fastapi_users import models

class AdminLogin(BaseModel):
	username: str
	password: str

class ResetPassword(BaseModel):
	old_password: str
	new_password: str
	new_repassword: str

class ForgotPassword(BaseModel):
	username: str
	new_password: str
	new_repassword: str
	auth_code: str

class AddNewAdminAcount(BaseModel):
	username: str
	init_password: str
	privilege: str

class DeleteAdminAcount(BaseModel):
	username: str

class BussinessConfig(BaseModel):
	MinerReward: float = 1000
	Level1Reward: float = 0.2
	Level2Reward: float = 0.3
	Level3Reward: float = 0.5
	MinerManageFee: float = 200
	MinerLife: int = 10000
	ShareReward: float = 100
	TeamBuyNumber: int = 5

class SystemSettings(BaseModel):
	pass
