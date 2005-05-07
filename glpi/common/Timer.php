<?
// Here's a useful class that will take care of your script timing in seconds/milliseconds.

class Script_Timer
{
	var $time=0;
  function Script_Timer ()
  {
    return true;
  }

  function Start_Timer ()
  {
    $this->time=microtime ();

    return true;
  }

  function Get_Time ($decimals = 3)
  {
    // $decimals will set the number of decimals you want for your milliseconds.

    // format start time
    $start_time = explode (" ", $this->time);
    $start_time = $start_time[1] + $start_time[0];
    // get and format end time
    $end_time = explode (" ", microtime ());
    $end_time = $end_time[1] + $end_time[0];

    return number_format ($end_time - $start_time, $decimals);
  }
}
?>