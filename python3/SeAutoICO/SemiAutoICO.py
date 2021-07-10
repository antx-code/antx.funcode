#-*-coding:utf8-*-


import requests
import time
import re
from selenium import webdriver
from selenium.webdriver.common.keys import Keys


driver = webdriver.Chrome('../../../../Pycharm/chromedriver')
ex_target_url = 'https://ico.info/projects/'
login_url = 'https://ico.info/sign_in'
ex_final_ico_page = 'https://ico.info/plans/'
nex_final_ico_page = '/orders/new'
user_agent = {'User-Agent':'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Ubuntu Chromium/59.0.3071.109 Chrome/59.0.3071.109 Safari/537.36'}
session = requests.Session()


def get_auth_token(login_page):
    """

    Get the authenticity_token from the source code page that is login page.

    :return:
    """

    login_page_source_codes = session.get(login_page,headers=user_agent).content
    login_codes = str(login_page_source_codes)
    auth_code = re.findall('"csrf-token" content="(.*?)"',login_codes)
    for auth in auth_code:
        return auth


def post_data(csrf_token,account,password):
    """

    We construct the post data that we'll handin when we need login the site.

    :return:
    """

    datas = {'utf8':'✓','authenticity_token':csrf_token,'user[email]':account,'user[password]':password,'commit':'login'}
    result = session.post(login_url,headers=user_agent,data=datas)
    return result


def get_agree_code(tar_url):
    """

    Get the agree code which we need to use to struct the agreement pages and the final ICO pages.

    :return:
    """

    driver.get(tar_url)
    time.sleep(0.5)
    driver.find_element_by_class_name("plan-support-btn-block").click()
    new_window = driver.window_handles
    driver.switch_to_window(new_window[1])
    time.sleep(0.5)
    agree_source_code = session.get(new_window,headers=user_agent).content
    agree_code_str = str(agree_source_code)
    agree_codes = re.findall('"btn btn-theme btn-block" href="/plans/(.*?)/accept_risk_warning"',agree_code_str)
    for agree_x in agree_codes:
        return agree_x


def final_deals(last_page):
    """

    Finish the last step to ICO.
    1. Input the num which we want to invest to the ICO.
    2. Get the question that need we to solve.
    3. Handle the request and finish the ICO.

    :return:
    """

    new_mark_page = driver.window_handles
    driver.switch_to_window(new_mark_page[1])
    driver.get(last_page)
    time.sleep(0.5)
    driver.find_element_by_class_name("numeric decimal optional nprice-input form-control").clear()
    driver.find_element_by_class_name("numeric decimal optional nprice-input form-control").sendkey(input("Please input the num"
                                                                                                          "that you want to invest"
                                                                                                          "the ICO:"))
    time.sleep(0.5)
    driver.find_element_by_class_name("numeric decimal optional nprice-input form-control").send_keys(Keys.TAB)
    handle_question = driver.find_element_by_class_name("title math-title").text
    print(handle_question)
    answer = input("Please input the answer:")
    driver.find_element_by_class_name("form-control").clear()
    driver.find_element_by_class_name("form-control").send_keys(answer)
    time.sleep(0.5)
    driver.find_element_by_class_name("btn btn-theme submit-btn disabled").click()
    time.sleep(0.5)


if __name__ == '__main__':
    project_num = input('Please input the ICO projects num:')
    target_url = ex_target_url + str(project_num)
    auth_cde = get_auth_token(login_url)
    login_account = input('Please input your ico.info account:')
    login_password = input('Please input your password:')
    post_data(auth_cde,login_account,login_password)
    agree_code = get_agree_code(target_url)
    final_ico_page = ex_final_ico_page + agree_code +nex_final_ico_page
    final_deals(final_ico_page)
