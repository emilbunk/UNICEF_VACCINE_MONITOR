import os, time, urllib2, httplib, csv, sys

from Adafruit_CharLCD import Adafruit_CharLCD

lcd = Adafruit_CharLCD()
lcd.clear()
lcd.message("Awaiting first\nmeasurments")

os.system('sudo modprobe w1-gpio')
os.system('sudo modprobe w1-therm')
os.system('gpio -g mode 10 out')

base_dir = '/sys/bus/w1/devices'

def get_settings():
	result = urllib2.urlopen("http://localhost/emoncms/raspberrypi/get.json")
	result = result.readline()
	result = result[1:-1].split(',')
	settings = {}
	for s in result:
		s = csv.reader([s], delimiter=':').next()
		settings[s[0]] = s[1].replace("\\","")
	return settings


def get_device_address():
	f = open(base_dir + '/w1_bus_master1/w1_master_slaves', 'r');
	devices = f.readlines()
	f.close()
	return devices
	
def remove_sensor(sensorAddress):
    tmp = "echo " + sensorAddress + " > sudo " + base_dir + "/w1_bus_master1/w1_master_remove"
    os.system(tmp)

def read_temp_raw(sensorAddress):
    device_file = base_dir + '/' + sensorAddress + '/w1_slave'
    f = open(device_file, 'r')
    lines = f.readlines()
    f.close()
    return lines

def read_temp(sensorAddress):
    lines = read_temp_raw(sensorAddress)
    for x in range(0, 5):
    	if lines[0].strip()[-3:] != 'YES':
        	time.sleep(0.2)
        	lines = read_temp_raw(sensorAddress)
        else:
        	break
        	
    if lines[0].strip()[-3:] != 'YES':
    	remove_sensor(sensorAddress)
    	return False
    
    equals_pos = lines[1].find('t=')
    temp_string = lines[1][equals_pos+2:]
    return temp_string[0:4]
        
def updateLCD(values, devices):
	if max(values) > 8:
		temp = "ALARM %+2.2f\n" %(float(max(values))/100)
		index = max(enumerate(values), key=operator.itemgetter(1))
		mes = devices[index] + temp
	elif min(values) < 2:
		temp = "ALARM %+2.2f\n" %(float(min(values))/100)
		index = min(enumerate(values), key=operator.itemgetter(1))
		mes = devices[index] + temp
	else:
		mes = "Cur. max:%+2.2f\n" %(float(max(values))/100)
		temp = "Cur. min:%+2.2f" %(float(min(values))/100)
		mes += temp
		
	lcd.clear()
	lcd.message(mes)

settings = get_settings()
readCount = 0

while True:
	readCount += 1
	devices = get_device_address()
	data = ""
	
	values = list()
	
	for address in devices:
		val = read_temp(address[:-1])
		if val != False:
			values.append(int(val))
			data += adrress[:-1] + ":" + val + ","
		else:
			data = None
			break
	
	if values:
		updateLCD(values, devices)
		
		if min(values) < 2 or max(values) > 8:
			os.system('gpio -g write 10 1')
			os.system('sh /home/pi/UNICEF_VACCINE_MONITOR/piezo_alarm.sh &')
		else:
			os.system('gpio -g write 10 0')
		
		if data and readCount > 1:
			url = "http://localhost/emoncms/input/post.json?json={" + data[:-1] + "&apikey=" + settings['apikey']
			urllib2.urlopen(url)
			url = settings['remoteprotocol'] + settings['remotedomain'] + settings['remotepath'] + "/input/post.json?json={" + data[:-1] + "}&apikey=" + settings['remoteapikey']
			urllib2.urlopen(url)
			readCount = 0
		
	time.sleep(10)
