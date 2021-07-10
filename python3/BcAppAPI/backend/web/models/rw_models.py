from pydantic import BaseModel
from typing import Optional

class GetAllRw(BaseModel):
    page: int
    size: int
    type: str

class GetOneRw(BaseModel):
    record_id: int

class UpdateOneRw(BaseModel):
    record_id: int
    status: str
