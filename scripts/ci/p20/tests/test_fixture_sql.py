import os
from pathlib import Path
import subprocess
import sys
import unittest
ROOT=Path(__file__).resolve().parents[1]
sys.path.insert(0,str(ROOT))
from service_targets import normalize_fixture_sql

class FixtureSql(unittest.TestCase):
    def test_only_error_stop_directive_is_normalized(self):
        body='DO $$ BEGIN PERFORM 1; END $$;\nSELECT 1;'
        self.assertEqual(normalize_fixture_sql('\\set ON_ERROR_STOP on\n'+body),body)
        with self.assertRaises(RuntimeError): normalize_fixture_sql('\\echo synthetic')

    def test_script_uses_exec_and_keeps_same_connection(self):
        code='''require $argv[1];
$db=new class { public int $calls=0; public function exec(string $sql): int {
 if($sql!=="SELECT 1; SELECT 2;")throw new RuntimeException(); $this->calls++; return 0;
} public function query(string $sql): never {throw new RuntimeException("QUERY_FOR_SCRIPT");}};
p20ExecuteFixture($db,"prepare","SELECT 1; SELECT 2;"); if($db->calls!==1)exit(1);
try {p20ExecuteFixture($db,"unknown","SELECT 1"); exit(2);} catch(RuntimeException) {}
'''
        p=subprocess.run([os.environ.get('P20_TEST_PHP','php'),'-r',code,str(ROOT/'fixture-sql.php')],capture_output=True,text=True)
        self.assertEqual(p.returncode,0,p.stderr)

if __name__=='__main__': unittest.main()
