// const puppeteer = require('puppeteer');
const puppeteer = require('puppeteer-extra')
const StealthPlugin = require('puppeteer-extra-plugin-stealth')
puppeteer.use(StealthPlugin())
var fs = require('fs');

(async ()=>{
    const browser = await puppeteer.launch({
        // executablePath:'/opt/google/chrome/google-chrome',
	slowMo:100,                                     // 在有输入的情况下的输入文本速度
        headless: false,                                 // 启用无头模式，默认启用
        defaultViewport :{                              // 浏览器窗体的大小设置
            width: 1280,
            height: 800},
        args:[                                          // 一些参数设置，对于过检测隐藏「window.navigator.webdriver」，在puppeteer的launch中将--enbale-automation注释掉即可
            '--disable-extensions',
            '--hide-scrollbars',
            '--disable-bundled-ppapi-flash',
            '--mute-audio',
            '--disable-gpu',
            '--disable-infobars'
        ],
        ignoreDefaultArgs:[
            '--enable-automation'
        ],
    });
    const page = await browser.newPage();               // 打开浏览器的一个新的page页面
    try{
        fs.access('cookie.json', fs.constants.F_OK | fs.constants.W_OK, (err) => {
            if (err) {
                throw err;
            } else {
                console.log('Json-DB中cookie有效，直接进行监控......')
            }
        });
    }catch (error){
        await console.log('Json-DB中cookie已过期，请重新登录并获取......')
        await page.goto('https://buff.163.com/',);   // Input your target url
        await page.waitFor(3000);
        await page.click('[onclick="loginModule.showLogin()"]');
        await console.log('请点击登录， 并勾选10天免登陆......');
        await page.waitForSelector('[class="j_drop-handler"]', {timeout:0});    // 登录成功后再进行后面的动作
    }finally {
        pre_cookie = await page.cookies();
        af_cookie = []
        for (var i=0; i<pre_cookie.length;i++){
            await af_cookie.push({'name':pre_cookie[i]['name'], 'value':pre_cookie[i]['value']})
        }
        await console.log(af_cookie)
        fs.readFile('cookie.json', 'utf8', function (err, data) {   // 判断文件是否存在，不存在就创建
            if (err) console.log(err);
            data = JSON.stringify(af_cookie)
            fs.writeFileSync('cookie.json', data, 'utf8', (err) => {    // 获取登录过后的cookie并保存
                if (err) throw err;
                console.log('done');
            });
        });
    }

    await page.close();
    await console.log('页面已关闭')
    await browser.close()
    await console.log('浏览器已关闭')
})();

