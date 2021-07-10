#-------------------------------------------------------------------------------
# Name:        execute_queries.py
# Purposes:    SQL文を一括で実行する.
#
# Author:      shikano.takeki
#
# Created:     07/12/2017
# Copyright:   shikano.takeki 2017
# Licence:     <your licence>
#-------------------------------------------------------------------------------
# -*- coding: utf-8 -*-
from lib.mysql_custom import MySQLDB
from getpass import getpass
from lib import msg_abstract
from lib import delete_null_row
import argparse


class ExcSql(object):
    """このクラスの説明.

    このクラスの説明.
    """

    def __init__(self, host: str, dst_db: str, myuser: str, mypass):
        """コンストラクタ.

        Args:
            param1 host: ホスト名.
            param2 dst_db: 接続先のデータベース名.
            param3 myuser: 接続ユーザー名.
            param4 mypass: 接続ユーザーのパスワード.
        """
        self.host = host
        self.dst_db = dst_db
        self.myuser = myuser
        self.mypass = mypass

        self.msg1 = msg_abstract.Msg1()
        self.msg2 = msg_abstract.Msg2()
        self.msg3 = msg_abstract.Msg3()
        self.msg4 = msg_abstract.Msg4()
        self.msg5 = msg_abstract.Msg5()
        self.msg6 = msg_abstract.Msg6()
        self.msg7 = msg_abstract.Msg7()
        self.msg8 = msg_abstract.Msg8()
        self.msg9 = msg_abstract.Msg9()
        self.msg10 = msg_abstract.Msg10()
        self.msg11 = msg_abstract.Msg11()
        self.msg12 = msg_abstract.Msg12()
        self.msg13 = msg_abstract.Msg13()
        self.msg14 = msg_abstract.Msg14()
        self.msg15 = msg_abstract.Msg15()

        self.dnr = delete_null_row.DeleteNullRow()

    def exc_sql(self):
        """メインメソッド.

        ファイルから1行ずつ読み込み、読み込んだ結果(リスト型)を実行する.
        トランザクション処理に入った場合は、処理を終える前に確認処理に移るかどうか
        を選択できる.
        """
        with MySQLDB(self.host, self.dst_db, self.myuser, self.mypass) as mysqldb:
            print(type(mysqldb))
            # ファイルオープン検査.
            dir_path, w_encoding = self._read_io_file(mode='r')
            # SQL文をファイルから読み込む.
            sqls = self.dnr.read_text_file(dir_path=dir_path, encode=w_encoding)
            # 読み込んだ結果を実行する.
            for sql in sqls:
                # トランザクション状態を管理.
                # トランザクション中 かつ COMMIT を実行する前に確認処理に移る.
                if mysqldb.is_transacted and 'COMMIT' in sql.upper():
                    self._confirm_before_commit(mysqldb, self.dnr)
                # トランザクション処理終了前の確認処理ここまで.
                    # 確認処理の後、このままCOMMITするか、ロールバックするかを尋ねる.
                    ans = self._input_int_answer(self.msg15)
                    if not self._judge_whether_commit(ans):
                        mysqldb.rollback()
                        print("ロールバックしました。\n")
                        continue
                # SQL文の実行.
                query_result = mysqldb.execute_sql(sql)
                print("実行したSQL文: {}".format(sql))
        print(__file__ + ' is ended.')

    def _confirm_before_commit(self, mysqldb: MySQLDB,
                                dnr: delete_null_row.DeleteNullRow):
        """トランザクション処理を終える前の一連の確認用処理をするメソッド.

        Args:
            param1 mysqldb: MySQLDBインスタンス.
            param2 dnr: DeleteNullRowインスタンス.

        Returns:
            Not returns values.
        """
        print("トランザクション処理中です。処理完了前に確認用のSQL文を実行しますか？")
        ans = self._input_int_answer(self.msg1)
        if ans == 1:
            print(self.msg4.call_msg())
            ans = self._input_int_answer(self.msg5)
            if ans == 1:
                while True:
                    self._input_sql_statement(self.msg6, mysqldb)
                    ans = self._input_int_answer(self.msg8)
                    if ans == 1:
                        continue
                    elif ans == 2:
                        break
            elif ans == 2:
                # ファイルオープン検査.
                # 入力ファイル
                r_dir_path, r_encoding = self._read_io_file(mode='r')
                # 出力ファイル
                w_dir_path, w_encoding = self._read_io_file(mode='a')
                # 入力ファイルからSQL文の抽出
                cfm_sqls = self.dnr.read_text_file(r'{}'.format(r_dir_path), encode=r_encoding)
                for single_sql in cfm_sqls:
                    result = mysqldb.execute_sql(single_sql)
                    # fetchall() is Returning a list of tuples. [(), (), (), ...]
                    rows = result.fetchall()
                    exc_statement = single_sql + ":\n"
                    # 実行結果の出力.
                    self._output_result(line=exc_statement, dir_path=w_dir_path, encode=w_encoding)
                    for i, row in enumerate(rows, start=1):
                        w_string = "Row" + str(i) + ": " + ', '.join(map(str, row))
                        self._output_result(line=w_string, dir_path=w_dir_path, encode=w_encoding)
                    self._output_result(line='\n', dir_path=w_dir_path, encode=w_encoding)
                print("==========================================\n")
                print("実行結果を {} に出力しました。\n".format(w_dir_path))
            else:
                print(self.msg3.call_msg())
        else:
            pass

    def _read_io_file(self, mode: str):
        """ファイル読み込み・検査用関数.

        Args:
            param1 mode: ファイルオープンモード.

        Returns:
            第一要素にディレクトリパス、第二要素に文字エンコーディングを格納した
            タプルを返す.

        Raises:
        """
        while True:
            if mode == 'r':
                dir_path = input(self.msg9.call_msg())
            elif mode in {'a', 'w'}:
                print(self.msg12.call_msg())
                dir_path = input(self.msg13.call_msg())
                if dir_path == '1':
                    return ("コンソール", "utf_8")
            print(self.msg10.call_msg())
            r_encoding = input(self.msg11.call_msg())
            if len(r_encoding) == 0:
                r_encoding = 'utf_8'
            if self.dnr.is_opened(dir_path=dir_path, mode=mode, encode=r_encoding):
                break
            else:
                continue
        return (dir_path, r_encoding)

    def _input_int_answer(self, msg_no):
        """標準入力から数値を受け取るためのメソッド.

        Args:
            param1 msg_no: メッセージナンバー.

        Returns:
            標準入力から受け取った数値.

        Raises:
            ValueError:
        """
        while True:
            try:
                ans = int(input(msg_no.call_msg()))
            except ValueError:
                print(self.msg2.call_msg())
                continue
            else:
                if ans >= 3 or ans <= 0:
                    print(self.msg3.call_msg())
                    continue
                break
        return ans

    def _input_sql_statement(self, msg_no, mysqldb: MySQLDB):
        """標準入力から文字列(SQL文)を受け取って実行するメソッド.

        1つ実行するとメソッドから抜ける.

        Args:
            param1 msg_no: メッセージナンバー.
            param2 mysqldb: MySQLDBインスタンス.

        Returns:
            Not returns values.

        Raises:
            mysql.connector.ProgrammingError:
        """
        cfm_sql = input(msg_no.call_msg())
        while True:
            try:
                result = mysqldb.execute_sql(cfm_sql)
            except mysql.connector.errors.ProgrammingError as e:
                print(e)
                print(self.msg7.call_msg())
                cfm_sql = input(msg_no.call_msg())
                continue
            else:
                print("実行結果: {}".format(result.fetchall()))
                break

    def _judge_whether_commit(self, answer: int):
        """コミットするかどうかの判断をするメソッド.

        Args:
            param1 answer: 標準入力から受け取った数値.

        Returns:
            1(COMMIT)ならTrue, 2(ROLLBACK) ならFalse
        """
        if answer == 1:
            return True
        elif answer == 2:
            return False
    def _output_result(self, line: str, dir_path: str, encode: str):
        """SQL文実行結果を出力するためのメソッド.

        Args:
            param1 line: SQL文の実行結果 単文.
            param2 dir_path: 書き込み先のディレクトリパス.
            param3 encode: 書き込み先ファイルの文字エンコーディング.

        Returns
            Not returns value.
        """
        if not dir_path == 'コンソール':
            line += '\n'
            self.dnr.write_text_file(line, r'{}'.format(dir_path), 'a', encode=encode)
        else:
            print(line)


def global_entry_point(self):
    """グローバルコマンド用のエントリーポイント."""
    argparser = argparse.ArgumentParser(description='クエリ実行用スクリプト')
    argparser.add_argument('--hostname', metavar='<HOSTUSER>', type=str, required=False,
                            default='127.0.0.1', help='接続対象となるホスト/IPを指定.')
    argparser.add_argument('-d', '--database', metavar='<DATABASE>', type=str,
                            required=True, help='接続先のデータベース名を指定.')
    argparser.add_argument('-u', '--user', metavar='<USER>', type=str, required=True,
                            help='接続するユーザ.')

    args = argparser.parse_args()

    password = getpass("{} ユーザのパスワード: ".format(args.user))

    excsql = ExcSql(host=args.hostuser, dst_db=args.database,
    myuser=args.user, mypass=password)
    excsql.exc_sql()

if __name__ == '__main__':
    argparser = argparse.ArgumentParser(description='クエリ実行用スクリプト')
    argparser.add_argument('--hostname', metavar='<HOSTUSER>', type=str, required=False,
                            default='127.0.0.1', help='接続対象となるホスト/IPを指定.')
    argparser.add_argument('-d', '--database', metavar='<DATABASE>', type=str,
                            required=True, help='接続先のデータベース名を指定.')
    argparser.add_argument('-u', '--user', metavar='<USER>', type=str, required=True,
                            help='接続するユーザ.')

    args = argparser.parse_args()

    password = getpass("{} ユーザのパスワード: ".format(args.user))

    excsql = ExcSql(host=args.hostuser, dst_db=args.database,
    myuser=args.user, mypass=password)
    excsql.exc_sql()
