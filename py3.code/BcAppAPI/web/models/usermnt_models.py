from pydantic import BaseModel
from typing import Optional

class User(BaseModel):
    email: str
    phone: str
    nickname: str
    invite_code: str
