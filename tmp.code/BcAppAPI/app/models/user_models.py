from pydantic import BaseModel
from typing import Optional
from fastapi_users import models

class UserRegisterLogin(BaseModel):
    email: Optional[str]
    phone: Optional[int]
    email_code: Optional[str]
    phone_code: Optional[int]
    invite_code: str

class UserLogout(models.BaseUser):
    email: Optional[str]
    phone: Optional[int]

class UserSetPassword(BaseModel):
    phone: int
    new_password: str
    new_repassword: str
    verify_code: str

class UserVerify(BaseModel):
    verify_type: str
    phone_email: str
    verify_code: str

class GetVerifyCode(BaseModel):
    verify_type: str
    phone_email: str
