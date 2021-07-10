from pydantic import BaseModel
from typing import Optional

class GetRecord(BaseModel):
    page: int
    size: int
    type: str
    user_id: int