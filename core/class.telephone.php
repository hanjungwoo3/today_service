<?php

/**
 * 25.01.15 with ChatGPT
 * Telephone 클래스
 */

class Telephone extends Core
{

    public function __construct($db)
    {
        parent::__construct($db); // Core 클래스의 생성자 호출
    }

    /**
     * 테이블의 기본값 반환
     * @return mixed 기본값
     */
    private function getDefaultValue()
    {
        // 기본값 목록
        $defaultValues = [
            'tp_num' => '',
            'tp_name' => '',
            'mb_id' => 0,
            'tp_mb_date' => '0000-00-00',
            'tp_assigned' => '',
            'tp_assigned_date' => '0000-00-00',
            'tp_assigned_group' => '',
            'tp_start_date' => '0000-00-00',
            'tp_end_date' => '0000-00-00',
            'm_id' => 0,
            'tp_memo' => '',
            'ms_id' => 0,
            'tp_status' => '',
            'tp_ms_all' => 0,
            'create_datetime' => 'NOW()',
            'update_datetime' => 'NOW()',
        ];
        return $defaultValues;
    }

    /**
     * 데이터 삽입
     * @param array $data 삽입할 데이터 배열 예: ['tt_name' => 'Example Name', 'tt_type' => 'Type1']
     * @return int 삽입된 데이터의 ID
     * @throws \InvalidArgumentException
     * @throws \Exception
     */
    public function insert($data)
    {

        $defaultValues = $this->getDefaultValue();
        // Core 클래스의 insertDB 메서드 호출
        return parent::insertDB(TELEPHONE_TABLE, 'tp_id', $defaultValues, $data);
    }

    /**
     * 데이터 수정
     * 받은 데이터만 업데이트 함
     * @param int $id 수정할 데이터의 ID
     * @param array $data Territory 수정 데이터 배열
     * @return int 수정 성공 시 해당 tt_id 반환
     * @throws \InvalidArgumentException
     * @throws \Exception SQL 실행 오류 발생 시 예외 처리
     */
    public function update($id, $data)
    {

        $defaultValues = $this->getDefaultValue();
        // Core 클래스의 updateDB 메서드 호출
        return parent::updateDB(TELEPHONE_TABLE, 'tp_id', $defaultValues, $id, $data);
    }

    /**
     * 데이터 삭제
     * @param int $id 삭제할 데이터의 ID
     * @return bool 삭제 성공 여부
     * @throws \InvalidArgumentException
     * @throws \Exception SQL 실행 오류 발생 시 예외 처리
     */
    public function delete($id)
    {

        // Core 클래스의 deleteDB 메서드 호출
        return parent::deleteDB(TELEPHONE_TABLE, 'tp_id', $id);
    }
    
}