<?php
namespace carono\yii2dumper;

class PostgreSqlDumper extends AbstractDumper
{
    public $port = 5432;

    public function isDevelopServer()
    {
        return true;
    }

    public function init()
    {
        putenv("PGPASSWORD=" . ($this->password ? $this->password : $this->getPassword()));
        putenv("PGUSER=" . ($this->user ? $this->user : $this->getUser()));
    }

    public function export()
    {
        $dir = $this->backup . DIRECTORY_SEPARATOR;
        $cmd = [
            "pg_dump",
            "-h",
            $this->getHost(),
            "-p",
            $this->getPort(),
            "-O",
            "-F" . ($this->compress ? "c" : "p"),
            "-b",
            "-v",
            "-f",
            $file = $dir . $this->formFileName(null, 'dump'),
            $this->getBaseName(),
        ];
        $this->exec($cmd);
        return $file;
    }

    public function import($file)
    {
        if ($this->isArchive($file)) {
            $cmd = [
                'pg_restore',
                "-h",
                $this->getHost(),
                "-p",
                $this->getPort(),
                '--clean',
                '--format=c',
                '--no-owner',
                '--dbname=' . $this->getBaseName(),
                $file
            ];
        } else {
            $cmd = [
                'psql',
                '--dbname=' . $this->getBaseName(),
                '-d ' . $this->getBaseName(),
                '-q',
                '-f',
                $file
            ];
        }
        $this->exec($cmd);
    }

    public function drop()
    {
        foreach ($this->getDbConnection()->getSchema()->tableNames as $table) {
            $command = $this->getDbConnection()->createCommand("DROP TABLE IF EXISTS \"$table\" CASCADE ;");
            $command->execute();
        }

        $sql = <<<SQL
SELECT      n.nspname as schema, t.typname as type 
FROM        pg_type t 
LEFT JOIN   pg_catalog.pg_namespace n ON n.oid = t.typnamespace 
WHERE       (t.typrelid = 0 OR (SELECT c.relkind = 'c' FROM pg_catalog.pg_class c WHERE c.oid = t.typrelid)) 
AND     NOT EXISTS(SELECT 1 FROM pg_catalog.pg_type el WHERE el.oid = t.typelem AND el.typarray = t.oid)
AND     n.nspname NOT IN ('pg_catalog', 'information_schema')
SQL;
        foreach ($this->getDbConnection()->createCommand($sql)->queryAll() as $row) {
            $this->getDbConnection()->createCommand('DROP TYPE ' . $row['type'] . ' CASCADE')->execute();
        }
    }
}