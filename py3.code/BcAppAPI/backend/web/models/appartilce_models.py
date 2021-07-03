from pydantic import BaseModel
from typing import Optional

class GetAllArticle(BaseModel):
    page: int
    size: int
    type: Optional[str]

class AddArticle(BaseModel):
	article_title: str
	article_content: str
	type: str

class GetOneArticle(BaseModel):
	article_id: int

class UpdateArticle(BaseModel):
    article_id: int
    title: Optional[str]
    content: Optional[str]

class DeleteArticle(BaseModel):
    article_id: int
