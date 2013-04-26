import os, time, urllib2, httplib, csv, sys
import RPi.GPIO as GPIO


from Adafruit_CharLCD import Adafruit_CharLCD

lcd = Adafruit_CharLCD()
lcd.clear()
lcd.message("Awaiting first\nmeasurments")

os.system('sudo modprobe w1-gpio')
os.system('sudo modprobe w1-therm')
time.sleep(10) # wait to give the 1-wire software time to find the connected sensors

GPIO.setmode(GPIO.BCM)
# Set led pin as out, and turn it of.
GPIO.setup(10, GPIO.OUT)
GPIO.setup(10, GPIO.LOW)

# Set optocoupler pin as in, where high readings means that a external power source is available.
GPIO.setup(8, GPIO.IN)

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

def read_temp_raw(sensorAddress):
	device_file = base_dir + '/' + sensorAddress + '/w1_slave'
	if os.path.exists(device_file):
		f = open(device_file, 'r')
		lines = f.readlines()
		f.close()
		return lines
	return ' '

def read_temp(sensorAddress):
    lines = read_temp_raw(sensorAddress)
    for x in range(0, 5):
    	if lines[0].strip()[-3:] != 'YES':
        	time.sleep(0.2)
        	lines = read_temp_raw(sensorAddress)
        else:
        	break
        	
    equals_pos = lines[1].find('t=')        	
    if lines[0].strip()[-3:] != 'YES' or  lines[1][equals_pos+1:equals_pos+3] =='85':
    	return False
    

    return lines[1][equals_pos+2:-2]
        
def updateLCD(values, devices):
	if max(values) > 8:
		temp = "ALARM %+2.2f\n" %(float(max(values))/100)
		index = values.index(max(values))
		mes = devices[index] + temp
	elif min(values) < 2:
		temp = "ALARM %+2.2f\n" %(float(min(values))/100)
		index = values.index(min(values))
		mes = devices[index] + temp
	else:
		mes = "Cur. max:%+2.2f\n" %(float(max(values))/100)
		temp = "Cur. min:%+2.2f" %(float(min(values))/100)
		mes += temp
		
	lcd.clear()
	lcd.message(mes)

settings = get_settings()
lastDataPush = time.time()
pushFreq = 10 # Data push to database in seconds

while True:
	devices = get_device_address()
	data = ""
	
	values = list()
	
	for address in devices:
		val = read_temp(address[:-1])
		if val != False:
			values.append(int(val))
			data += address[:-1] + ":" + val + ","
		else:
			data = None
			break
	
	if values:
		updateLCD(values, devices)
		
		if min(values) < 200 or max(values) > 800:
			GPIO.output(10, GPIO.HIGH)
			os.system('sh /home/pi/UNICEF_VACCINE_MONITOR/piezo_alarm.sh &')
		else:
			GPIO.output(10, GPIO.LOW)
		
	if data and time.time() - lastDataPush > pushFreq:
		print("data pushed")
		data += ("power-source:" + str(GPIO.input(8)))
		url = "http://localhost/emoncms/input/post.json?json={" + data + "&apikey=" + settings['apikey']
		urllib2.urlopen(url)
		url = settings['remoteprotocol'] + settings['remotedomain'] + settings['remotepath'] + "/input/post.json?json={" + data + "}&apikey=" + settings['remoteapikey']
		urllib2.urlopen(url)
		lastDataPush = time.time()
