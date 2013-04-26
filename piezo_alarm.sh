#Shell script for piezo alarm on Raspberry pi
#Using pin 18 (PWM)

tone () {
  local note="$1" time="$2"
  if test "$note" -eq 0; then
    gpio -g mode 18 in
  else
    local period="$(perl -e"printf'%.0f',600000/440/2**(($note-69)/12)")"
    gpio -g mode 18 pwm
    gpio pwmr "$((period))"
    gpio -g pwm 18 "$((period/2))"
    gpio pwm-ms
  fi
  sleep "$time"
}

tone 59 0.2
tone 60 0.2
tone 0 0
tone 59 0.2
tone 60 0.2
tone 0 0
