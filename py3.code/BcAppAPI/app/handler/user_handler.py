from loguru import logger
import qrcode
from io import BytesIO
from utils.services.redis_db_connect.connect import *
import subprocess

@logger.catch(level='ERROR')
def after_register(email_phone, nickname, user_id):
	after_register_info = {
		'nickname': nickname,
		'user_id': user_id
	}
	if isinstance(email_phone, str):
		after_register_info['email'] = email_phone
	else:
		after_register_info['phone'] = email_phone
	return after_register_info

@logger.catch(level='ERROR')
def send_sms_code():
	pass

@logger.catch(level='ERROR')
def send_email_code():
	pass

@logger.catch(level='ERROR')
def verify_email_code():
	pass

@logger.catch(level='ERROR')
def verify_sms_code():
	pass

def promo_code(user_id: int) -> str:
	table = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ-_"
	s = bin(user_id)[2:][::-1]
	return "".join([table[int(s[i : i + 6][::-1], 2)] for i in range(0, len(s), 6)][::-1])[:8]

def generate_qrcode(user_id, promo_code):
	mongodb = db_connection('bc-app', 'promo_qrcode')
	qr = qrcode.QRCode(
		version=5,  # 二维码的大小，取值1-40
		box_size=10,  # 二维码最小正方形的像素数量
		error_correction=qrcode.constants.ERROR_CORRECT_H,  # 二维码的纠错等级
		border=5  # 白色边框的大小
	)
	qr.add_data(promo_code)  # 设置二维码数据
	img = qr.make_image()  # 创建二维码图片
	img.save(f'{promo_code}_qrcode.png')  # 保存二维码make()
	with open(f'{promo_code}_qrcode.png', 'rb') as f:
		img_png = BytesIO(f.read())
	mongodb.save_img(user_id, img_png, promo_code)
	subprocess.run(f'rm {promo_code}_qrcode.png', shell=True)

