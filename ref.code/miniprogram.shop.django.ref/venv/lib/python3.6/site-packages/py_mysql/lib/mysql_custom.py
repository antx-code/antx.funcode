#-------------------------------------------------------------------------------
# Name:        muysql_custom.py
# Purpose:     MySQL謹製のmysql-connector-pythonのラッパークラス
# を実装したモジュール.
#
# Author:      shikano.takeki
#
# Created:     08/12/2017
# Copyright:   shikano.takeki 2017
# Licence:     <your licence>
#-------------------------------------------------------------------------------
# -*- coding: utf-8 -*-
import mysql.connector
from getpass import getpass


class MySQLDB(object):
    """MySQL謹製のmysql-connector-pythonのラッパークラス"""
    def __init__(self, host:str, dst_db: str, myuser: str, mypass: str):
        """コンストラクタ.

        各種初期化を行う.
        :param host: データベースの接続ホスト.
        :param dst_db: 接続先のデータベース名.
        :param myuser: 接続するユーザー名.
        :param mypass: 接続するユーザのパスワード.
        """
        self.host = host
        self.dst_db = dst_db
        self.myuser = myuser
        self.mypass = mypass
        self._conn = None
        self._cur = None

        # 初期化時にDBに接続する.
        self.connect(self.host, self.dst_db, self.myuser, self.mypass)

    def __enter__(self):
        """コンテキストマネージャ実装のため."""
        return self

    def __exit__(self, exc_type, exc_val, exc_tb):
        """コンテキストマネージャ実装のため.

        :param exc_type:
        :param exc_val:
        :param exc_val:
        :param exc_tb:
        """
        self.close()
        if not self.is_connect:
            print("DB接続をクローズしました。")

    def connect(self, host: str, dest_db: str, myuser: str, mypass: str):
        """MySQL接続用関数.

        """
        try:
            self._conn = mysql.connector.connect(user=self.myuser,
                password=self.mypass,
                database=self.dst_db,
                host=self.host)
        except mysql.connector.errors.ProgrammingError as e1:
            print("正確なアカウント情報、DB名を指定してください。")
            hostname = input("hostname: ")
            username = input("user: ")
            password = getpass()
            database = input("destination_db: ")
            self._conn = mysql.connector.connect(user=username,
                    password=password,
                    database=database,
                    host=hostname)
        else:
            if self._conn.is_connected():
                print("DB名：{} への接続成功.".format(self.dst_db))
                # カーソルの取得
                print(self._conn)
                self._cur = self._conn.cursor()
                return self

    def close(self):
        """接続しているデータベースのクローズを行う."""
        if self._conn.is_connected():
            self._cur.close()
            self._conn.close()
            self._conn = None
            self._cur = None

    def commit(self):
        """接続しているデータベースへのコミットを行う."""
        self._conn.commit()

    def rollback(self):
        """トランザクション処理をロールバックする."""
        self._conn.rollback()

    def fetchone(self):
        """カーソルから次の1レコードを取得する.

        Returns a tuple or None.
        """
        return self._cur.fetchone()

    def fetchall(self):
        """カーソルから全レコードを取得する.

        Returns a list of tuples.
        """
        return self._cur.fetchall()

    def is_connect(self) -> bool:
        """データベースへの接続状況を教えてくれる."""
        return self._conn.is_connected()

    def get_cursor(self):
        """カーソルを取得する."""
        return self._cur

    def execute_sql(self, sql: str, params=None):
        """SQL文の実行をする.

        :param sql: 実行するクエリ、コマンド
        :param params: 割り当て用のパラメータ 要タプル型"""
        # 接続確認
        if not self.is_connect:
            self.connect(self.host, self.dst_db, self.myuser, self.mypass)
        try:
            cur = self.get_cursor()
            if params is None:
                self._cur.execute(sql)
            else:
                # パラメータがタプル型かどうかを検査する.
                # タプル型でなかった場合例外を発生させる.
                if not isinstance(params, tuple):
                    params = tuple(params)
                    if not isinstance(params, tuple):
                        raise ValueError("割り当てパラメータはタプル型でなければなりません。")
                self._cur.execute(sql)
        except mysql.connector.errors.OperationalError as e1:
            raise e1
        except mysql.connector.errors.ProgrammingError as e2:
            raise e2
        else:
            return self._cur

    def execute_sqls(self):
        """複数のSQL文を実行する."""

    def is_transacted(self) -> bool:
        """トランザクションの状態を取得する.

        トランザクション処理中ならばTrueを返す.
        """
        return self._conn.in_transaction()

