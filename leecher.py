import libtorrent as lt
import time
import sys
from datetime import datetime

ses = lt.session()
ses.listen_on(6881, 6891)
params = {
    'save_path': 'files/',
    'storage_mode': lt.storage_mode_t(1),
    'paused': False,
    'auto_managed': True,
    'duplicate_is_error': True
    }

link = sys.argv[1]
logfile = sys.argv[2]
WAIT_CNT = 0
f = open(logfile, 'w+')
handle = lt.add_magnet_uri(ses, link, params)
ses.start_dht()
start=datetime.now()
start_time=start.strftime("%d/%m/%Y %H:%M:%S")
f.write('[*] '+start_time+'\n')
f.write('[*] Getting file info\n')
while (not handle.has_metadata()):
    WAIT_CNT += 1
    time.sleep(1)
    if WAIT_CNT == 180: # wait for 3mins
       f.write('[*] '+link+'\n[*] Unable to get file info due to very few seeds\n[*] Process Terminated')
       f.close()
       sys.exit()
f.write('[*] '+handle.status().name+'\n')
while (handle.status().state != lt.torrent_status.seeding):
    time.sleep(10)
    s = handle.status()
    f.write('[*] %6.2f%% [Speed: %7.1fKB/s|Seeds:%3d|Peers:%3d] [%s]\n' % (s.progress * 100, s.download_rate / 1024, s.num_seeds, s.num_peers, s.state))
end=datetime.now()
f.write('[*] Completed\n')
end_time=end.strftime("%d/%m/%Y %H:%M:%S")
f.write('[*] '+end_time)
f.close()
sys.exit()
