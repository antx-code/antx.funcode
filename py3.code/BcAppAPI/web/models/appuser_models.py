from pydantic import BaseModel
from typing import Optional

class AddUser(BaseModel):
    email: Optional[str]
    phone: Optional[str]
    nickname: str
    init_password: str = '123456789'

class GetUser(BaseModel):
    user_id: int

class UpdateUser(BaseModel):
    user_id: int
    update_info: dict

class DeleteUser(BaseModel):
    user_id: int
