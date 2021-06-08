from loguru import logger
from pydantic import BaseModel
from typing import Optional
from fastapi_users import models
from fastapi_users.db import MongoDBUserDatabase
from fastapi_users import FastAPIUsers, InvalidPasswordException
from fastapi_users.authentication import JWTAuthentication

class User(models.BaseUser):
    pass

class UserRegisterEmail(models.BaseUserCreate):
    repassword: str
    nickname: str
    phone: int
    email_code: str
    phone_code: int
    invite_code: str

class UserRegisterPhone(models.BaseUser):
    phone: int
    password: str
    repassword: str
    nickname: str
    verify_code: str
    invite_code: str

class UserLogin(models.BaseUser):
    email: Optional[str]
    phone: Optional[int]
    password: str
    phone_code: Optional[str]

class UserLogout(models.BaseUser):
    email: Optional[str]
    phone: Optional[int]

class UserResetPassword(models.BaseUser):
    phone: int
    old_password: Optional[str]
    new_password: str
    new_repassword: str
    verify_code: str

class UserVerify(BaseModel):
    verify_type: str
    phone_email: str
    verify_code: str

class VerifyCode(BaseModel):
    verify_type: str
    phone_email: str

class UserUpdate(User, models.BaseUserUpdate):
    pass

class UserDB(User, models.BaseUserDB):
    pass