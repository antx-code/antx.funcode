#-*-coding:utf8-*-

import steam.webauth as wa
import requests
from requests import Session
import re
from decimal import Decimal
import time
from random import randint as randt
import json

header = {'User-Agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/87.0.4280.66 Safari/537.36 Aoyou/VnN1RG0qQ1hkKz0iT1h0WW39_ebQ7Og6EiMFYYb9HbCN9c3PheedkDIN'}
COMMUNITY_URL = 'https://steamcommunity.com'
CREATE_BUY_ORDER = '/market/createbuyorder/'
GAME_DIC = {'STEAM':'753','CS':'730','DOTA':'570'}
CURRENCY = {
    'CNY': 23,
    'USD': 1,
    'EUR': 3,
    'JPY': 8,
    'AUD': 21,
    'CAD': 20,
    'RUB': 5,
}
SEARCH_URL = f'https://steamcommunity.com/market/search?q='


buff_trade_url = 'https://buff.163.com/api/market/steam_trade?_=1606985806092'

def buff163_login_auth():
    cookie = {'Cookie': 'mail_psc_fingerprint=8cbd1bf3e398778cfdcafb4d9f9a602d; _ntes_nnid=3cf958667905a54282b8a46a8a939eac,1606353484470; _ntes_nuid=3cf958667905a54282b8a46a8a939eac; Device-Id=i9jisszHoiH2SXuOGEbb; game=csgo; _ga=GA1.2.829811385.1606902291; _gid=GA1.2.358269194.1606902291; Locale-Supported=zh-Hans; NTES_YD_SESS=yL_ibjPfua2ypFk0.QhGHXOdBP51untrpLWxEy8_46IM3iTX3xgZlsw.f3FbUarGsLMex1rWXuu.zqgHtGhJXxGGaOKKP8m9waL5DSDLVXKtipVyWGEx7aBaNbI2PnQZhKaLzMs1Xogo2xkt8S0CMgVgAzma5dB_XnTul_0tjkB7BIUP4PrifbBL2RSfYvejFEHMWYwQyU6hCUg.FvCk7UAHmn2nGXZUPgld6VMDPnfk9; S_INFO=1606976086|0|3&80##|13613905817; P_INFO=13613905817|1606976086|1|netease_buff|00&99|AU&1606975457&netease_buff#hen&410100#10#0#0|&0|null|13613905817; remember_me=U1094423637|qFgYK8KwOze8PmKgVbrhnzaY5m7HcnwI; session=1-60CnA7ZNHExwszSvD_XWJRySHepfgYinlp2rlJWWhrIP2046102285; _gat_gtag_UA_109989484_1=1; csrf_token=IjRjNDU5ODYyYjk2N2IzMmYxZjgxMGY3N2QzYWRkYTIxMWY2OWZmOTYi.Eqo5-w.riHxQrjECGaH6OiVB4AO0Kynzx8'}
    buff_session = Session()
    resp = buff_session.get(url='https://buff.163.com/api/market/steam_trade?_=1606985806092', headers=header, cookies=cookie).content.decode('utf-8')
    print(resp)

def monitor_buff():
    buff_session = Session()
    while True:
        resp = buff_session.get(url='https://buff.163.com/api/market/steam_trade?_=1606985806092', headers=header, cookies=cookie).content.decode('utf-8')
        if len(json.loads(resp)['data']) > 0:
            break


def steam_login_auth(username, passwd):
    user = wa.WebAuth(username, passwd)
    session = user.login(twofactor_code=input('请输入 2FA Code:'), language='schinese')
    return session

def deal_exchange():
    pass

def alarm():
    pass

if __name__ == '__main__':
    buff163_login_auth()
