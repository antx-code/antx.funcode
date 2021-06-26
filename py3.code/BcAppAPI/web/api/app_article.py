from fastapi import APIRouter, Depends, UploadFile, File
from fastapi.responses import RedirectResponse
from loguru import logger
import json
import time
from io import BytesIO
from utils.exceptions.customs import InvalidPermissions, UnauthorizedAPIRequest, RecordNotFound
from utils.services.base.base_func import *
from utils.services.redis_db_connect.connect import *
from utils.services.base.SnowFlake import IdWorker
from web.models.appartilce_models import *

id_worker = IdWorker(0, 0)

# logger.add(sink='logs/app_article.log',   (包含公告和活动)
#            level='ERROR',
#            # colorize=True,     # 设置颜色
#            format='{time:YYYY-MM-DD HH:mm:ss} - {level} - {file} - {line} - {message}',
#            enqueue=True,
#            # serialize=True,    # 序列化为json
#            backtrace=True,   # 设置为'False'可以保证生产中不泄露信息
#            diagnose=True,    # 设置为'False'可以保证生产中不泄露信息
#            rotation='00:00',
#            retention='7 days')

router = APIRouter(dependencies=[Depends(antx_auth)])

user_db = db_connection('bc-app', 'users')
user_info_db = db_connection('bc-app', 'user-info')
promo_db = db_connection('bc-app', 'promo_qrcode')
dnk_db = db_connection('bc-app', 'dnetworks')
miner_db = db_connection('bc-app', 'miners')
asset_db = db_connection('bc-app', 'assets')
miner_pic_db = db_connection('bc-app', 'miner_pics')
record_db = db_connection('bc-app', 'records')
share_buy_db = db_connection('bc-app', 'share_buy_code')
admin_db = db_connection('bc-web', 'admin_users')
article_db = db_connection('bc-app', 'articles')
announcement_db = db_connection('bc-app', 'announcement')
redis_service = redis_connection(redis_db=0)


@logger.catch(level='ERROR')
@router.post('/all')
async def get_record(get_info: GetAllArticle):
	articles = []
	pref = (get_info.page - 1) * get_info.size
	af = get_info.size
	try:
		article_info = article_db.collection.find({'type': get_info.type}, {"_id": 0}).skip(pref).limit(af)
		for article in article_info:
			articles.append(article)
	except Exception as e:
		if get_info.type == 'announcement':
			try:
				article_info = announcement_db.collection.find({}, {"_id": 0}).skip(pref).limit(af)
				for article in article_info:
					articles.append(article)
			except Exception as e:
				pass
	return msg(status='success', data=articles)

@logger.catch(level='ERROR')
@router.post('/add_article')
async def add_article(request: Request, add_info: AddArticle):
	user_id = antx_auth(request)
	username = admin_db.find_one({'user_id': user_id})['username']
	article_id = id_worker.get_id()
	now_time = time.strftime('%Y-%m-%d %H:%M:%S', time.localtime(time.time()))
	article_info = {
		'aid': article_id,
		'title': add_info.article_title,
		'content': add_info.article_content,
		'created_time': now_time,
		'created_by': user_id,
		'author': username,
		'type': add_info.type
	}
	article_db.insert_one_data(article_info)
	if add_info.type == 'announcement':
		del article_info['type']
		del article_info['created_by']
		announcement_db.insert_one_data(article_info)
	return msg(status='success', data=f'Add new {add_info.type} successfully')

@logger.catch(level='ERROR')
@router.post('/get_article')
async def get_article(get_info: GetOneArticle):
	article_info = article_db.find_one({'aid': get_info.article_id})
	if not article_info:
		return msg(status='error', data='Article are not exist!')
	return msg(status='success', data=article_info)

@logger.catch(level='ERROR')
@router.post('/update_article')
async def update_article(update_info: UpdateArticle):
	updates = {}
	infos = dict(update_info)
	del infos['article_id']
	for inx, (k, v) in enumerate(infos.items()):
		if v:
			updates[k] = v
	article_db.update_one({'aid': update_info.article_id}, updates)
	ori = article_db.find_one({'aid': update_info.article_id})
	if ori['type'] == 'announcement':
		del ori['type']
		del ori['created_by']
		announcement_db.update_one({'aid': update_info.article_id}, ori)
	return msg(status='success', data='Update article successfully')

@logger.catch(level='ERROR')
@router.post('/delete_article')
async def delete_article(delete_info: DeleteArticle):
	article_info = article_db.find_one({'aid': delete_info.article_id})
	logger.info(article_info)
	article_db.delete_one({'aid': delete_info.article_id})
	if article_info['type'] == 'announcement':
		announcement_db.delete_one({'aid': delete_info.article_id})
	return msg(status='success', data='Article was deleted successfully')
