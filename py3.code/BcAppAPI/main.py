from fastapi import FastAPI, Request
from starlette.middleware.cors import CORSMiddleware
from app.api.user import router as user_router
from app.api.user_info import router as user_info_router
from app.api.team import router as team_router
from app.api.miner import router as miner_router
from utils.exceptions.customs import InvalidPermissions, UnauthorizedAPIRequest, RecordNotFound, InvalidAPIRequest, ServerError, DatabaseError, InvalidContentType, RecordAlreadyExists
from fastapi.responses import JSONResponse
from loguru import logger

@logger.catch(level='ERROR')
def generate_application() -> FastAPI:
    application = FastAPI(title='BC-APP-API', version='v1', description='Created by antx at 2021-06-04.', redoc_url=None)
    application.debug = False

    application.add_middleware(
        CORSMiddleware,
        allow_origins=["*"],
        allow_credentials=True,
        allow_methods=["*"],
        allow_headers=["*"],
    )

    register_exception(application)

    application.include_router(
        user_router,
        prefix="/api/app/user",
        tags=["APP-USER API"],
        responses={404: {"description": "Not found"}}
    )

    application.include_router(
        user_info_router,
        prefix="/api/app/user_info",
        tags=["APP-USER-INFO API"],
        responses={404: {"description": "Not found"}}
    )

    application.include_router(
        team_router,
        prefix="/api/app/team",
        tags=["APP-TEAM API"],
        responses={404: {"description": "Not found"}}
    )

    application.include_router(
        miner_router,
        prefix="/api/app/miner",
        tags=["APP-MINER API"],
        responses={404: {"description": "Not found"}}
    )

    return application

    # application.include_router(
    #     users_router,
    #     prefix="/api/web",
    #     tags=["BC-WEB API"],
    #     responses={404: {"description": "Not found"}}
    # )
    #
    # return application

@logger.catch(level='ERROR')
def register_exception(app: FastAPI):
    """
    全局异常捕获
    注意 别手误多敲一个s
    exception_handler
    exception_handlers
    两者有区别
        如果只捕获一个异常 启动会报错
        @exception_handlers(UserNotFound)
    TypeError: 'dict' object is not callable
    :param app:
    :return:
    """

    @app.exception_handler(InvalidPermissions)
    async def request_InvalidPermissions_exception_handler(request: Request, exc: InvalidPermissions):
        """
        请求参数验证异常
        :param request:
        :param exc:
        :return:
        """

        return JSONResponse(status_code=exc.status_code, content=exc.to_dict())

    @app.exception_handler(UnauthorizedAPIRequest)
    async def request_UnauthorizedAPIRequest_exception_handler(request: Request, exc: UnauthorizedAPIRequest):
        """
        请求参数验证异常
        :param request:
        :param exc:
        :return:
        """

        return JSONResponse(status_code=exc.status_code, content=exc.to_dict())

    @app.exception_handler(RecordNotFound)
    async def request_RecordNotFound_exception_handler(request: Request, exc: RecordNotFound):
        """
        请求参数验证异常
        :param request:
        :param exc:
        :return:
        """

        return JSONResponse(status_code=exc.status_code, content=exc.to_dict())

    @app.exception_handler(RecordAlreadyExists)
    async def request_RecordAlreadyExists_exception_handler(request: Request, exc: RecordAlreadyExists):
        """
        请求参数验证异常
        :param request:
        :param exc:
        :return:
        """

        return JSONResponse(status_code=exc.status_code, content=exc.to_dict())

    @app.exception_handler(InvalidAPIRequest)
    async def request_InvalidAPIRequest_exception_handler(request: Request, exc: InvalidAPIRequest):
        """
        请求参数验证异常
        :param request:
        :param exc:
        :return:
        """

        return JSONResponse(status_code=exc.status_code, content=exc.to_dict())

    @app.exception_handler(ServerError)
    async def request_ServerError_exception_handler(request: Request, exc: ServerError):
        """
        请求参数验证异常
        :param request:
        :param exc:
        :return:
        """

        return JSONResponse(status_code=exc.status_code, content=exc.to_dict())

    @app.exception_handler(DatabaseError)
    async def request_DatabaseError_exception_handler(request: Request, exc: DatabaseError):
        """
        请求参数验证异常
        :param request:
        :param exc:
        :return:
        """

        return JSONResponse(status_code=exc.status_code, content=exc.to_dict())

    @app.exception_handler(InvalidContentType)
    async def request_InvalidContentType_exception_handler(request: Request, exc: InvalidContentType):
        """
        请求参数验证异常
        :param request:
        :param exc:
        :return:
        """

        return JSONResponse(status_code=exc.status_code, content=exc.to_dict())

    # 捕获全部异常
    @app.exception_handler(Exception)
    async def all_exception_handler(request: Request, exc: Exception):
        """
        全局所有异常
        :param request:
        :param exc:
        :return:
        """

        return JSONResponse(status_code=500, content='Unknown Error!')

app = generate_application()