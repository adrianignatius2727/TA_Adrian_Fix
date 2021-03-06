﻿using System;
using System.Collections.Generic;
using System.Text;

namespace SahabatSurabaya
{
    class LostFoundReportParams
    {
        public User userLogin { get; set; }
        public UploadedImage imageLaporan { get; set; }

        public int jenisLaporan { get; set; }
        public string descLaporan { get; set; }

        public string lat { get; set; }

        public string lng { get; set; }

        public string judulLaporan { get; set; }

        public string tglLaporan { get; set; }

        public string waktuLaporan { get; set; }

        public string displayJenisBarang { get; set; }

        public string alamatLaporan { get; set; }

        public string valueJenisBarang { get; set; }

        public string namaFileGambar { get; set; }

        public LostFoundReportParams(User userLogin, string judulLaporan, int jenisLaporan, string lat, string lng, string descLaporan, string tglLaporan, string displayJenisBarang, string valueJenisBarang, UploadedImage imageLaporan, string alamatLaporan, string waktuLaporan, string namaFileGambar)
        {
            this.userLogin =userLogin;
            this.judulLaporan = judulLaporan;
            this.jenisLaporan = jenisLaporan;
            this.descLaporan = descLaporan;
            this.lat = lat;
            this.lng = lng;
            this.imageLaporan = imageLaporan;
            this.tglLaporan = tglLaporan;
            this.displayJenisBarang = displayJenisBarang;
            this.valueJenisBarang = valueJenisBarang;
            this.alamatLaporan = alamatLaporan;
            this.waktuLaporan = waktuLaporan;
            this.namaFileGambar = namaFileGambar;
        }
    }
}
