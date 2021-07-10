from fastapi import APIRouter, Depends
from utils.services.base.base_func import *
from utils.services.redis_db_connect.connect import *
from web.models.apprecord_models import *

# logger.add(sink='logs/app_record.log',
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
redis_service = redis_connection(redis_db=0)


@logger.catch(level='ERROR')
@router.post('/get_record')
async def get_record(get_record: GetRecord):
	records = []
	pref = (get_record.page - 1) * get_record.size
	af = get_record.size
	try:
		record_info = record_db.collection.find({'type': get_record.type, 'user_id': get_record.user_id}, {"_id": 0}).skip(pref).limit(af)
		for record in record_info:
			records.append(record)
	except Exception as e:
		pass
	return msg(status='success', data=records)
