#
# true_tar = 'Device-Id=ksrKqrhnXI3IbCO86D7w; _ga=GA1.2.470761462.1607086108; mail_psc_fingerprint=45b1e99d012b773f63c65a533c20153a; _ntes_nnid=04439bfec1d965edc0aeb61194697258,1607086284303; _ntes_nuid=04439bfec1d965edc0aeb61194697258; Locale-Supported=zh-Hans; game=csgo; _gid=GA1.2.1096537323.1607878503; NTES_YD_SESS=XrouU0N_.PqdNRe1rZfR0HBLLr_7FBeNj7vaUPyLU4jVAZ_WA7Kw6gH8JA0uTL1PgcVb7E1zWhh8nIKNmPStW7PPL5vvqjQ3HLcoYxYckWviINz9SgCLB1dWtrSxdcbQtQVXXRyypnLKgXa17C9T3iadf6lbk_jaSKjwPhfr9hOo0VNIsFwQpA7Szy_M4yimVFlzD2McqOZMaNdSUrWgE9fSEWDsBTr1v6s6z768CkOD3; S_INFO=1607878526|0|3&80##|13613905817; P_INFO=13613905817|1607878526|1|netease_buff|00&99|hen&1607528515&netease_buff#hen&410100#10#0#0|&0|null|13613905817; remember_me=U1094423637|jnrmtSRxKq6V60MTkoPAaNyLpkn83Zwe; session=1-p9xAC_fzWxoTw2bcjtwO2oyEtiFC1DPf7jPP898LuhLM2046102285; csrf_token=IjE3N2JkN2I0MzBjZjNlMmZhYzcxNDdkZTk1Nzk3ZjI5Y2EzZTc3YWMi.Erj2ew.nSZjUwpZWiDV3ceJBLeMUjUAqfU'
# faslse_t = 'NTES_YD_SESS=vMCV7tP88kVs7kSV28ntvm0SKufrKloicw82mkKsmFtyTPr9Tw.qDBIJ7TM4QshkBgypwSh39ddJA5.lekiR9wkksz88OtbUIsgnLZLgX98G5l3jiB1sEh09RuiZ0gpbRbyvvHKK6As.Bv2hw1jQUG20xDopXrt2i.tqkdxujdWtbemYKHlCwAmNw2mnpyjiNDIwzMcoSkHW5ZQPYeNyZ2RiS9aCEQuh8DCD3wDJ1XWaU,session=1-gE_O3fDNUdrU9AZt7H69Aa84OS782ms_MU3yjoUGUF4H2046102285,P_INFO=13613905817|1607951408|1|netease_buff|00&99|hen&1607521575&netease_buff#hen&410100#10#0#0|&0|null|13613905817,S_INFO=1607951408|0|3&80##|13613905817,Device-Id=ryg79YDMYwxX5Iuj1GFe,Locale-Supported=en,_ga=GA1.2.586750795.1607951347,_gid=GA1.2.1096665063.1607951347,game=csgo,_gat_gtag_UA_109989484_1=1,csrf_token=IjM5MzllOGNlMDQ1OGI2MDNkODI3NzZiNTc5M2I2ZTU5OTk3YzE3MDQi.Erj1xA.MvjrENwLM46MMLtLyrDRdiwQDn0,remember_me=U1094423637|sMR5Yr8bVcHH6m8XraosAoCtO7ypfY83'
#
# tar_list1 = []
# tar_list2 = []
# af1 = true_tar.split(',')
# af2 = faslse_t.split(',')
# for each in af1:
# 	tar_list1.append(each.split('=')[0])
#
# for each in af2:
# 	tar_list2.append(each.split('=')[0])
# print(tar_list1)
# print(tar_list2)
#
import re
buyer_join_date = '2015年3月13日'
tar = '2015-03-13'
tars = tar.split('-')
print(tars)
buyer_join_date_list = re.findall('(.*?)年(.*?)月(.*?)日', buyer_join_date)[0]
print(buyer_join_date_list)
if buyer_join_date_list[0] == tars[0] and int(buyer_join_date_list[1]) == int(tars[1]) and int(buyer_join_date_list[2]) == int(tars[2]):
	print(111)
else:
	print(222)
