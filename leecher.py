import libtorrent as lt
import time
import sys
import os
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
f.write('[*] ['+start_time+']\n[*] Getting file info\n')
f.flush()
while (not handle.has_metadata()):
    WAIT_CNT += 1
    time.sleep(1)
    if WAIT_CNT == 180: # wait for 3mins
       f.write('[*] '+link+'\n[*] Unable to get file info due to very few seeds\n[*] Process Terminated')
       f.close()
       sys.exit()
f.write('[*] Name : '+handle.status().name+'\n    Size : %d MB\n' % round(handle.status().total_wanted / 1000000))
f.flush()
while (handle.status().state != lt.torrent_status.seeding):
    time.sleep(1)
    s = handle.status()
    f.write('[*]%6.1f%% [Speed:%5d KB/s|Seeds:%3d|Peers:%3d]' % (s.progress * 100, round(s.download_rate / 1024), s.num_seeds, s.num_peers))
    f.flush()
    f.seek(f.tell()-49, os.SEEK_SET)
end=datetime.now()
end_time=end.strftime("%d/%m/%Y %H:%M:%S")
f.seek(0, os.SEEK_END)
f.write('\n[*] Completed\n[*] ['+end_time+']')
f.close()
sys.exit()
