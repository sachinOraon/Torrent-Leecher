import libtorrent as lt
import time
import sys

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
f = open(logfile, 'w+')
handle = lt.add_magnet_uri(ses, link, params)
ses.start_dht()
f.write('[*] Getting file info\n')
while (not handle.has_metadata()):
    time.sleep(1)
f.write('[*] '+handle.status().name+'\n')
while (handle.status().state != lt.torrent_status.seeding):
    time.sleep(5)
    s = handle.status()
    f.write('[*] %6.2f%% [Speed: %7.1fKB/s|Seeds:%3d|Peers:%3d] [%s]\n' % (s.progress * 100, s.download_rate / 1024, s.num_seeds, s.num_peers, s.state))
f.write('[*] Completed')
f.close()
sys.exit()