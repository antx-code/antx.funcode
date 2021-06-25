from pydantic import BaseModel
from typing import Optional
from fastapi_users import models

class AdminLogin(BaseModel):
	username: str
	password: str

class AdminLogout(BaseModel):
	username: str

class ResetPassword(BaseModel):
	username: str
	old_password: str
	new_password: str
	new_repassword: str

class ForgotPassword(BaseModel):
	username: str
	new_password: str
	new_repassword: str
	identity: str

class AddNewAdminAcount(BaseModel):
	username: str
	init_password: str
	privilege: str

class DeleteAdminAcount(BaseModel):
	username: str
