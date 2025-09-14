<?php

/**
 * 25.01.15 with ChatGPT
 * Core 클래스
 */

class Core
{

    /**
     * @var mysqli $db 데이터베이스 연결 객체
     */
    protected $db;

    /**
     * 생성자
     * @param mysqli $db 데이터베이스 연결 객체
     * @throws \InvalidArgumentException 잘못된 객체가 전달될 경우 예외 처리
     */
    public function __construct($db)
    {
        if (!$db instanceof mysqli) {
            throw new \InvalidArgumentException('Expected instance of mysqli');
        }
        $this->db = $db;
    }

    /**
     * 배열의 참조값을 반환
     * @param array $arr 배열
     * @return array 참조값 배열
     */
    protected function refValues($arr)
    {
        if (strnatcmp(phpversion(), '5.3') >= 0) {
            $refs = [];
            foreach ($arr as $key => $value) {
                $refs[$key] = &$arr[$key];
            }
            return $refs;
        }
        return $arr;
    }

    /**
     * 테이블의 컬럼 목록 가져오기
     * @param string $tableName 테이블 이름
     * @return array 컬럼 이름 배열
     * @throws \Exception SQL 실행 오류 시 예외 처리
     */
    protected function getTableColumns($tableName)
    {
        $sql = "DESCRIBE $tableName";
        $result = $this->db->query($sql);
    
        if (!$result) {
            throw new \Exception('Failed to fetch table columns: ' . $this->db->error);
        }
    
        // 컬럼 이름을 배열로 반환
        $columns = [];
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row['Field']; // 컬럼 이름은 'Field' 키로 반환됩니다.
        }
    
        return $columns;
    }

    /**
     * 데이터 삽입 메서드
     * 입력된 데이터를 기반으로 테이블에 레코드를 삽입합니다.
     * - 입력 데이터에서 실제 테이블에 존재하는 컬럼만 필터링.
     * - 누락된 값은 기본값으로 대체.
     * - 자동 증가 컬럼은 제외.
     * 
     * @param string $tableName 테이블 이름
     * @param string $autoIncrementColumn 자동 증가 컬럼 이름
     * @param array $defaultValues 테이블 기본값 배열
     * @param array $data 삽입할 데이터 배열 (컬럼명 => 값)
     * @return int 삽입된 레코드의 ID
     * @throws \InvalidArgumentException 입력 데이터가 비어 있는 경우
     * @throws \Exception SQL 실행 오류가 발생한 경우
     */
    protected function insertDB($tableName, $autoIncrementColumn, $defaultValues, $data)
    {

        // 테이블 이름이 비어 있으면 예외 발생
        if (empty($tableName)) {
            throw new \InvalidArgumentException('Table name must be provided for insert.');
        }

        // 자동 증가 컬럼이 비어 있으면 예외 발생
        if (empty($autoIncrementColumn)) {
            throw new \InvalidArgumentException('Auto-increment column must be provided for insert.');
        }

        // 기본값 배열이 비어 있으면 예외 발생
        if (empty($defaultValues) || !is_array($defaultValues)) {
            throw new \InvalidArgumentException('Default values must be provided as a non-empty array.');
        }

        // 삽입 데이터가 비어 있으면 예외 발생
        if (empty($data)) {
            throw new \InvalidArgumentException('Insert data must not be empty.');
        }

        // 테이블의 실제 컬럼 목록을 가져오기 (예: PDO 사용)
        $columns = $this->getTableColumns($tableName); // 테이블 이름에 맞게 수정
        $filteredData = [];

        // 전달된 데이터와 기본값 병합 처리
        foreach ($columns as $column) {
            if ($column === $autoIncrementColumn) {
                continue; // 자동 증가 컬럼 제외
            }
        
            if (array_key_exists($column, $data) && $data[$column] !== '') {
                $filteredData[$column] = $data[$column]; // 전달된 값 사용
            } else {
                $filteredData[$column] = isset($defaultValues[$column]) ? $defaultValues[$column] : null; // 기본값 사용
            }
        }

        // 필터링된 데이터 중 NOW()가 필요한 경우 처리
        $keys = [];
        $placeholders = [];
        $values = [];
        foreach ($filteredData as $key => $value) {
            $keys[] = "`" . $this->db->real_escape_string($key) . "`";
            if ($value === 'NOW()') {
                $placeholders[] = 'NOW()';
            } else {
                $placeholders[] = '?';
                $values[] = $value;
            }
        }
        
        $keysString = implode(',', $keys);
        $placeholdersString = implode(',', $placeholders);
        $sql = "INSERT INTO ".$tableName." ($keysString) VALUES ($placeholdersString)";
        $stmt = $this->db->prepare($sql);
        
        if (!$stmt) {
            throw new \Exception('Failed to prepare statement: ' . $this->db->error);
        }
        
        $types = str_repeat('s', count($values));
        $bindParams = array_merge([$types], $values);
        call_user_func_array([$stmt, 'bind_param'], $this->refValues($bindParams));
        
        if (!$stmt->execute()) {
            throw new \Exception('SQL Error: ' . $stmt->error . ' | Query: ' . $sql);
        }

        // 삽입된 데이터의 ID 반환
        $insertId = $stmt->insert_id;

        // Prepared Statement 종료
        $stmt->close();

        return $insertId;
    }

    /**
     * 데이터 수정
     * 받은 데이터만 업데이트 함
     * @param string $tableName 테이블 이름
     * @param string $autoIncrementColumn 자동 증가 컬럼 이름
     * @param array $defaultValues 테이블 기본값 배열
     * @param int $id 수정할 데이터의 ID
     * @param array $data Territory 수정 데이터 배열
     * @return int 수정 성공 시 해당 tt_id 반환
     * @throws \InvalidArgumentException
     * @throws \Exception SQL 실행 오류 발생 시 예외 처리
     */
    protected function updateDB($tableName, $autoIncrementColumn, $defaultValues, $id, $data)
    {

        // 테이블 이름이 비어 있으면 예외 발생
        if (empty($tableName)) {
            throw new \InvalidArgumentException('Table name must be provided for update.');
        }

        // 자동 증가 컬럼이 비어 있으면 예외 발생
        if (empty($autoIncrementColumn)) {
            throw new \InvalidArgumentException('Auto-increment column must be provided for update.');
        }

        // 기본값 배열이 비어 있으면 예외 발생
        if (empty($defaultValues) || !is_array($defaultValues)) {
            throw new \InvalidArgumentException('Default values must be provided as a non-empty array.');
        }

        // 수정할 데이터의 id가 비어 있으면 예외 발생
        if (empty($id)) {
            throw new \InvalidArgumentException('Update id must not be empty.');
        }

        // 삽입 데이터가 비어 있으면 예외 발생
        if (empty($data)) {
            throw new \InvalidArgumentException('Update data must not be empty.');
        }

        // 테이블의 실제 컬럼 목록 가져오기
        $allowedColumns = $this->getTableColumns($tableName);

        // 전달된 데이터에서 허용된 컬럼만 필터링
        $filteredData = [];
        foreach ($data as $key => $value) {
            if (in_array($key, $allowedColumns, true)) {
                // 빈 문자열 처리: 기본값으로 대체
                if ($value === '') {
                    $value = isset($defaultValues[$key]) ? $defaultValues[$key] : null; // 기본값 사용
                }
                $filteredData[$key] = $value; // 데이터만 추가
            }
        }

        if (empty($filteredData)) {
            throw new \InvalidArgumentException('No valid columns provided for update');
        }
        
        // 필드와 값 바인딩 준비
        $fields = [];
        $values = [];
        foreach ($filteredData as $column => $value) {
            $fields[] = "`" . $this->db->real_escape_string($column) . "` = ?";
            $values[] = $value;
        }

        // 동적으로 SET 절 생성
        $setClause = implode(', ', $fields);

        // UPDATE 쿼리 생성
        $sql = "UPDATE " . $tableName . " SET $setClause, `update_datetime` = NOW() WHERE `".$autoIncrementColumn."` = ?";

        // Prepared Statement 준비
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            throw new \Exception('Failed to prepare statement: ' . $this->db->error);
        }

        // 값 바인딩: 데이터 + ID
        $types = str_repeat('s', count($values)) . 'i'; // 문자열 개수 + ID(int) 추가
        $values[] = $id;

        $bindParams = array_merge([$types], $values);
        call_user_func_array([$stmt, 'bind_param'], $this->refValues($bindParams));

        // 쿼리 실행
        if (!$stmt->execute()) {
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                throw new \Exception('SQL Error: ' . $stmt->error . ' | Query: ' . $sql);
            } else {
                throw new \Exception('SQL execution failed. Please contact the administrator.');
            }
        }

        // 성공적으로 수정되었는지 확인
        if ($stmt->affected_rows > 0) {
            $successId = $id; // 수정 성공 시 ID 저장
        } else {
            $successId = 0; // 수정되지 않은 경우 0 반환
        }

        $stmt->close();

        if ($successId === 0) {
            throw new \Exception('No rows affected. Update might have failed or no changes were made.');
        }

        return $successId;

    }

    /**
     * 데이터 삭제
     * @param string $tableName 테이블 이름
     * @param string $autoIncrementColumn 자동 증가 컬럼 이름
     * @param int $id 삭제할 데이터의 ID
     * @return bool 삭제 성공 여부
     * @throws \InvalidArgumentException
     * @throws \Exception SQL 실행 오류 발생 시 예외 처리
     */
    public function deleteDB($tableName, $autoIncrementColumn, $id)
    {

        // 테이블 이름이 비어 있으면 예외 발생
        if (empty($tableName)) {
            throw new \InvalidArgumentException('Table name must be provided for delete.');
        }

        // 자동 증가 컬럼이 비어 있으면 예외 발생
        if (empty($autoIncrementColumn)) {
            throw new \InvalidArgumentException('Auto-increment column must be provided for delete.');
        }

        // ID 유효성 검사
        if ($id <= 0) {
            throw new \InvalidArgumentException('Invalid ID provided for delete.');
        }

        // DELETE 쿼리 생성
        $sql = "DELETE FROM ".$tableName." WHERE ".$autoIncrementColumn." = ?";

        // Prepared Statement 준비
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            // Statement 준비 실패 시 예외 처리
            throw new \Exception('Failed to prepare statement: ' . $this->db->error);
        }

        // 값 바인딩
        $stmt->bind_param('i', $id); // ID를 정수형으로 바인딩

        // 쿼리 실행
        if (!$stmt->execute()) {
            // 실행 실패 시 예외 처리
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                throw new \Exception('SQL Error: ' . $stmt->error . ' | Query: ' . $sql);
            } else {
                throw new \Exception('SQL execution failed. Please contact the administrator.');
            }
        }

        // 영향을 받은 행 수 확인 (0일 경우 데이터 없음)
        $success = $stmt->affected_rows > 0;

        // Prepared Statement 종료
        $stmt->close();

        // 삭제 성공 여부 반환
        return $success;
    }


}