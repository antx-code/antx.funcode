import time
from utils.services.redis_db_connect.connect import *
from utils.services.auth.auth import *
from utils.services.base.base_func import *
from config import CONFIG
import bson
from bson import  ObjectId
from io import BytesIO
import base64
# from PIL import Image
from io import StringIO

def test_redis_incr():
	redis_service = redis_connection()
	res = redis_service.redis_client.incr(name='wkaifeng', amount=1)
	print(res)
	print(type(res))
	resp = redis_service.set_dep_key('wkaifeng', res, 90)
	print(resp)

def test_get_user_id():
	mongodb = db_connection('bc-app', 'users')
	result = mongodb.find_one({'email': 'wkaifeng2007@163.com'})
	# for user in result:
	# 	user_id = user['user_id']
	# print(user_id)
	print(result['user_id'])
	print(result)

def test_create_token():
	access_token = create_authtoken(user_id='111', identity='bcb-app')['access_token']
	print(access_token)
	from fastapi_users.authentication import JWTAuthentication
	return access_token

def test_mongodb_update_one():
	mongodb = db_connection('bc-app', 'users')
	now_time = time.strftime('%Y-%m-%d %H:%M:%S', time.localtime(time.time()))
	login_info = {
		'last_login_time': now_time,
		'access_token': test_create_token()
	}
	result = mongodb.update_one({'email': 'wkaifeng2007@163.com'}, login_info)
	print(result)

def number2hex():
	import binascii

	def baseN(num, b):
		return ((num == 0) and "0") or \
		       (baseN(num // b, b).lstrip("0") + "0123456789abcdefghijklmnopqrstuvwxyz"[num % b])

	# s = 3470044405556051968
	s = baseN(3470044405556051968, 32)

	print(hex(3470044405556051968))
	print(baseN(3470044405556051968, 32))
	str_16 = binascii.b2a_hex(str(s).encode('utf-8'))  # 字符串转16进制
	print(str_16)

def p64(n: int) -> str:
	table = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ-_"
	s = bin(n)[2:][::-1]
	return "".join([table[int(s[i : i + 6][::-1], 2)] for i in range(0, len(s), 6)][::-1])[:8]

def test_snow_flake():
	from utils.services.base.SnowFlake import IdWorker
	id_worker = IdWorker(0, 0)
	user_id = id_worker.get_id()
	print(user_id)
	return user_id

def test_qrcode(promo_code):
	from app.handler.user_info_handler import generate_qrcode
	generate_qrcode(promo_code)

def save_img():
	with open('promo_code.png', 'rb') as f:
		img = BytesIO(f.read())
	# bimg = bson.binary.Binary(img.getvalue())
	# result = base64.b64encode(bimg).decode('ascii')
	mongodb = db_connection('test', 'test')
	r = mongodb.save_img(3470044405556051968, img)
	print(r)
	# print(result)
	# return result

def restore_img(data=None):
	# ori_data = base64.b64decode(data)
	# with open('test.png', 'wb') as f:
	# 	f.write(ori_data)
	mon = db_connection('test', 'test')
	r = mon.read_img(3470044405556051968, 'test2.png', save_local=True)
	print(r)

def test_phone():
	regre = '\+(9[976]\d|8[987530]\d|6[987]\d|5[90]\d|42\d|3[875]\d|2[98654321]\d|9[8543210]|8[6421]|6[6543210]|5[87654321]|4[987654310]|3[9643210]|2[70]|7|1)\W*\d\W*\d\W*\d\W*\d\W*\d\W*\d\W*\d\W*\d\W*(\d{1,2})$'
	phone_number = re.findall(regre, '+8615738368019')
	logger.info(phone_number)

def generate_init_miners():
	from utils.services.redis_db_connect.connect import db_connection
	mongodb = db_connection('bc-app', 'miners')
	miner_info = {
		'miner_name': 'MinerMonkey',
		'miner_month_reward': 1000,
		'miner_power': '4THS',
		'miner_price': 2000,
		'miner_team_price': 1800,
		'miner_manage_price': 0.1,
		'miner_numbers': 10000
	}
	mongodb.insert_one_data(miner_info)

def set_default_avatar():
	from utils.services.redis_db_connect.connect import db_connection
	mongodb = db_connection('bc-app', 'avatar')
	with open('default.png', 'rb') as f:
		png_content = f.read()
	mongodb.save_img(user_id='default', img=png_content)

def init_asset():
	from utils.services.redis_db_connect.connect import db_connection
	asset_db = db_connection('bc-app', 'assets')

	asset_info = {
		'user_id': 3471749054516428800,
		'asset': {
			'usdt': {
				'all': 1000,
				'today_reward': 10,
			},
			'miner': [{
				'miner_id': 0,
				'alive_time': '00:00:00',
				'all': 0,
				'today_reward': 0,
			}],
			'team_miner': [{
				'miner_id': 0,
				'alive_time': '00:00:00',
				'members': [],  # nickname
				'all': 0,
				'today_rewards': 0,
				'today_reward': 0
			}]
		}
	}
	asset_db.update_one({'user_id': 3471749054516428800}, asset_info)

if __name__ == '__main__':
	# generate_init_miners()
	# set_default_avatar()
	init_asset()
	# test_phone()
	# test_redis_incr()
	# test_get_user_id()
	# test_create_token()
	# test_mongodb_update_one()
	# number2hex()
	# res = p64(test_snow_flake())
	# res = p64(3470044405556051968)
	# print(res)
	# test_qrcode(res)
	# save_img()
	# restore_img()
