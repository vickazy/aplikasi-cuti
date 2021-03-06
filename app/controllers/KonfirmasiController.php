<?php
	class KonfirmasiController extends BaseController
	{
		private $id;

		public function showDataKonfirmasi()
		{
			return View::make('konfirmasi.konfirmasi')
							->with(array('pengajuan' => $this->getDataUntukDikonfirmasiOleh(Auth::user()->jabatan)));
		}	

		private function getDataUntukDikonfirmasiOleh($jabatan)
		{
			if ('pimpinan' == $jabatan) {
				return Pengajuan::belumDisetujuiPimpinan()
									->whereHas('karyawan', function($q){
										$q->where('id_bagian', '=', Auth::user()->id_bagian);
										$q->where('disetujui_pimpinan', '<>', 2);
									})->get();
			} else if ('hr' == $jabatan) {
				return Pengajuan::where('disetujui_pimpinan', '=', 1)
									->where('disetujui_hr', '=', 0)
									->where('disetujui_pimpinan', '<>', 2)
									->where('disetujui_hr', '<>', 2)
									->get();
			}
		}

		public function setuju($id)
		{
			$this->id = $id;
			$this->setSetujuOleh(Auth::user()->jabatan);
			return Redirect::to('konfirmasi');
		}

		private function setSetujuOleh($jabatan)
		{
			$pengajuan = Pengajuan::find($this->id);
			if ('pimpinan' == $jabatan) {
				$pengajuan->disetujui_pimpinan = 1;
				$pengajuan->status = '<label class="moderate">Menunggu Persetujuan HR</label>';
			} else if ('hr') {
				$pengajuan->disetujui_hr = 1;
				$pengajuan->status = '<label class="success">Diterima</label>';

				//beritahu user
				Notifikasi::create(array(
					'to' => $pengajuan->nik, 
					'status' => 'unread', 
					'content' => "Pengajuan Cuti " . $pengajuan->cuti->nama . " tanggal $pengajuan->tanggal telah disetujui"
				));
			}
			$pengajuan->save();
		}

		public function tolak($id)
		{
			$this->id = $id;
			$this->setTolakOleh(Auth::user()->jabatan);

			return Redirect::to('konfirmasi');
		}

		private function setTolakOleh($jabatan)
		{
			$pengajuan = Pengajuan::find($this->id);
			if ('pimpinan' == $jabatan) {
				$pengajuan->disetujui_pimpinan = 2;
			} else if ('hr') {
				$pengajuan->disetujui_hr = 2;

							
				//beritahu user
				Notifikasi::create(array(
					'to' => $pengajuan->nik, 
					'status' => 'unread', 
					'content' => "Pengajuan Cuti " . $pengajuan->cuti->nama . " tanggal $pengajuan->tanggal ditolak"
				));

			} 
			$pengajuan->status = '<label class="danger">Ditolak</label>';
			$pengajuan->save();
		}
	}