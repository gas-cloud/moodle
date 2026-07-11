<?php
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

class local_examoverride_external extends external_api {

    // ==========================================================
    // create_group_override — bikin baru ATAU update kalau sudah ada
    // ==========================================================
    public static function create_group_override_parameters() {
        return new external_function_parameters([
            'quizid'    => new external_value(PARAM_INT, 'ID instance quiz (bukan cmid)'),
            'groupid'   => new external_value(PARAM_INT, 'ID grup Moodle'),
            'timeopen'  => new external_value(PARAM_INT, 'Timestamp buka, 0 = tidak di-override', VALUE_DEFAULT, 0),
            'timeclose' => new external_value(PARAM_INT, 'Timestamp tutup, 0 = tidak di-override', VALUE_DEFAULT, 0),
            'timelimit' => new external_value(PARAM_INT, 'Durasi dalam detik, -1 = tidak di-override', VALUE_DEFAULT, -1),
            'password'  => new external_value(PARAM_RAW, 'Password quiz, kosong = tidak di-override', VALUE_DEFAULT, ''),
            'attempts'  => new external_value(PARAM_INT, 'Jumlah percobaan, -1 = tidak di-override', VALUE_DEFAULT, -1),
        ]);
    }

    public static function create_group_override($quizid, $groupid, $timeopen, $timeclose, $timelimit, $password, $attempts) {
        global $DB;

        $params = self::validate_parameters(self::create_group_override_parameters(), [
            'quizid' => $quizid, 'groupid' => $groupid, 'timeopen' => $timeopen,
            'timeclose' => $timeclose, 'timelimit' => $timelimit, 'password' => $password, 'attempts' => $attempts,
        ]);

        $quiz = $DB->get_record('quiz', ['id' => $params['quizid']], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('quiz', $quiz->id, $quiz->course, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/quiz:manageoverrides', $context);

        // Pastikan grup itu beneran ada di course yang sama dengan quiz
        $group = $DB->get_record('groups', ['id' => $params['groupid'], 'courseid' => $quiz->course], '*', MUST_EXIST);

        $record = new stdClass();
        $record->quiz = $quiz->id;
        $record->groupid = $group->id;
        $record->userid = null;
        $record->timeopen = $params['timeopen'] > 0 ? $params['timeopen'] : null;
        $record->timeclose = $params['timeclose'] > 0 ? $params['timeclose'] : null;
        $record->timelimit = $params['timelimit'] >= 0 ? $params['timelimit'] : null;
        $record->attempts = $params['attempts'] >= 0 ? $params['attempts'] : null;
        $record->password = $params['password'] !== '' ? $params['password'] : null;

        $existing = $DB->get_record('quiz_overrides', ['quiz' => $quiz->id, 'groupid' => $group->id]);

        if ($existing) {
            $record->id = $existing->id;
            $DB->update_record('quiz_overrides', $record);
            $overrideid = $record->id;
            $action = 'updated';
        } else {
            $overrideid = $DB->insert_record('quiz_overrides', $record);
            $action = 'created';
        }

        return ['status' => true, 'overrideid' => (int)$overrideid, 'action' => $action];
    }

    public static function create_group_override_returns() {
        return new external_single_structure([
            'status'     => new external_value(PARAM_BOOL, 'Berhasil atau tidak'),
            'overrideid' => new external_value(PARAM_INT, 'ID override'),
            'action'     => new external_value(PARAM_TEXT, 'created atau updated'),
        ]);
    }

    // ==========================================================
    // delete_group_override
    // ==========================================================
    public static function delete_group_override_parameters() {
        return new external_function_parameters([
            'quizid'  => new external_value(PARAM_INT, 'ID instance quiz'),
            'groupid' => new external_value(PARAM_INT, 'ID grup Moodle'),
        ]);
    }

    public static function delete_group_override($quizid, $groupid) {
        global $DB;

        $params = self::validate_parameters(self::delete_group_override_parameters(), [
            'quizid' => $quizid, 'groupid' => $groupid,
        ]);

        $quiz = $DB->get_record('quiz', ['id' => $params['quizid']], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('quiz', $quiz->id, $quiz->course, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/quiz:manageoverrides', $context);

        $existing = $DB->get_record('quiz_overrides', ['quiz' => $quiz->id, 'groupid' => $params['groupid']]);
        if (!$existing) {
            return ['status' => false, 'msg' => 'Override tidak ditemukan untuk quiz+grup ini.'];
        }

        $DB->delete_records('quiz_overrides', ['id' => $existing->id]);

        return ['status' => true, 'msg' => 'Override berhasil dihapus.'];
    }

    public static function delete_group_override_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_BOOL, 'Berhasil atau tidak'),
            'msg'    => new external_value(PARAM_TEXT, 'Pesan'),
        ]);
    }

    // ==========================================================
    // get_group_overrides — baca semua override grup di 1 quiz
    // ==========================================================
    public static function get_group_overrides_parameters() {
        return new external_function_parameters([
            'quizid' => new external_value(PARAM_INT, 'ID instance quiz'),
        ]);
    }

    public static function get_group_overrides($quizid) {
        global $DB;

        $params = self::validate_parameters(self::get_group_overrides_parameters(), ['quizid' => $quizid]);

        $quiz = $DB->get_record('quiz', ['id' => $params['quizid']], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('quiz', $quiz->id, $quiz->course, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/quiz:manageoverrides', $context);

        $records = $DB->get_records_select('quiz_overrides', 'quiz = :quiz AND userid IS NULL', ['quiz' => $quiz->id]);

        $result = [];
        foreach ($records as $r) {
            $group = $DB->get_record('groups', ['id' => $r->groupid], 'id, name');
            $result[] = [
                'overrideid' => (int)$r->id,
                'groupid'    => (int)$r->groupid,
                'groupname'  => $group ? $group->name : '',
                'timeopen'   => $r->timeopen !== null ? (int)$r->timeopen : 0,
                'timeclose'  => $r->timeclose !== null ? (int)$r->timeclose : 0,
                'timelimit'  => $r->timelimit !== null ? (int)$r->timelimit : -1,
                'attempts'   => $r->attempts !== null ? (int)$r->attempts : -1,
                'password'   => $r->password !== null ? $r->password : '',
            ];
        }

        return ['status' => true, 'overrides' => $result];
    }

    public static function get_group_overrides_returns() {
        return new external_single_structure([
            'status'    => new external_value(PARAM_BOOL, 'Berhasil atau tidak'),
            'overrides' => new external_multiple_structure(
                new external_single_structure([
                    'overrideid' => new external_value(PARAM_INT, 'ID override'),
                    'groupid'    => new external_value(PARAM_INT, 'ID grup'),
                    'groupname'  => new external_value(PARAM_TEXT, 'Nama grup'),
                    'timeopen'   => new external_value(PARAM_INT, 'Timestamp buka, 0 = tidak diset'),
                    'timeclose'  => new external_value(PARAM_INT, 'Timestamp tutup, 0 = tidak diset'),
                    'timelimit'  => new external_value(PARAM_INT, 'Durasi detik, -1 = tidak diset'),
                    'attempts'   => new external_value(PARAM_INT, 'Jumlah percobaan, -1 = tidak diset'),
                    'password'   => new external_value(PARAM_RAW, 'Password, kosong = tidak diset'),
                ])
            ),
        ]);
    }
}
