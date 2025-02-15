<?php namespace App\SUtils;

/**
 * Estructura Utilizada para los renglones de los reporte de 
 * retardos y horas extras
 */
class SRegistryRow {
    function __construct() {
        $this->idEmployee = 0;
        $this->numEmployee = 0;
        $this->employee = 0;
        $this->external_id = 0;
        $this->inDate = null;
        $this->inDateTime = null;
        $this->inDateTimeSch = null;
        $this->inDateTimeNoficial = null;
        $this->outDate = null;
        $this->outDateTime = null;
        $this->outDateTimeSch = null;
        $this->scheduleText = "";
        $this->outDateTimeNoficial = null;
        $this->entryDelayMinutes = null;
        $this->prematureOut = null;
        $this->diffMins = null;
        $this->delayMins = null;
        $this->overDefaultMins = null;
        $this->overScheduleMins = null;
        $this->overWorkedMins = null;
        $this->overMinsByAdjs = null;
        $this->adjusts = [];
        $this->overMinsTotal = null;
        $this->cutId = null;
        $this->extraHours = "00:00";
        $this->hasAbsence = false;
        $this->workable = true;
        $this->isSunday = 0;
        $this->isDayOff = 0;
        $this->isHoliday = 0;
        $this->extraDouble = "00:00";
        $this->extraDoubleMins = 0;
        $this->extraTriple = "00:00";
        $this->extraTripleMins = 0;
        $this->extraDoubleNoficial = "00:00";
        $this->extraDoubleMinsNoficial = 0;
        $this->extraTripleNoficial = "00:00";
        $this->extraTripleMinsNoficial = 0;
        $this->dayInhability = 0;
        $this->dayVacations = 0;
        $this->events = [];
        $this->hasAssign = false;
        $this->hasChecks = true;
        $this->hasSchedule = true;
        $this->isSpecialSchedule = false;
        $this->isOnSchedule = true;
        $this->hasWorkedJourney8hr = false;
        $this->isOverJourney = false;
        $this->overtimeCheckPolicy = 2;
        $this->isDayRepeated = false;
        $this->scheduleFrom = 0;
        $this->hasCheckOut = true;
        $this->hasCheckIn = true;
        $this->isAtypicalIn = false;
        $this->isAtypicalOut = false;
        $this->isCheckSchedule = false;
        $this->isTypeDayChecked = false;
        $this->hasAdjust = false;
        $this->work_dayoff = 0;
        $this->others = "";
        $this->comments = "";
        
    }
}

?>


