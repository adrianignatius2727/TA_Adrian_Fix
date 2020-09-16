﻿using Newtonsoft.Json;
using Newtonsoft.Json.Linq;
using Plugin.FilePicker;
using Plugin.FilePicker.Abstractions;
using System;
using System.Collections.Generic;
using System.Collections.ObjectModel;
using System.Diagnostics;
using System.Globalization;
using System.Linq;
using System.Net.Http;
using System.Threading;
using Windows.UI.Popups;
using Windows.UI.Xaml;
using Windows.UI.Xaml.Controls;
using Windows.UI.Xaml.Navigation;
using Xamarin.Essentials;

// The Blank Page item template is documented at https://go.microsoft.com/fwlink/?LinkId=234238

namespace SahabatSurabaya
{

    public sealed partial class MakeCrimeReportPage : Page
    {
        DispatcherTimer dispatcherTimer;
        int tick = 0;
        bool isChosen = false;
        string lat, lng = "";
        int imageCount = 0;
        Session session;
        List<UploadedImage> listImage;
        List<SettingKategori> listSetingKategoriKriminalitas;
        ObservableCollection<AutocompleteAddress> listAutoCompleteAddress;
        User userLogin;
        public MakeCrimeReportPage()
        {
            this.InitializeComponent();
            listImage = new List<UploadedImage>();
            listSetingKategoriKriminalitas = new List<SettingKategori>();
            listAutoCompleteAddress = new ObservableCollection<AutocompleteAddress>();
            session = new Session();
            dispatcherTimer = new DispatcherTimer();
            dispatcherTimer.Interval = new TimeSpan(0, 0, 0, 0, 200);
            dispatcherTimer.Tick += DispatcherTimer_Tick;

        }

        private void DispatcherTimer_Tick(object sender, object e)
        {
            tick++;
            if (tick == 2 && txtAutocompleteAddress.Text.Length != 0 && !isChosen)
            {
                searchAutocomplete();
            }
            if (isChosen && tick == 2) isChosen = false;
        }

        private async void suggestionChosen(object sender, ItemClickEventArgs e)
        {
            isChosen = true;
            AutocompleteAddress item = (AutocompleteAddress)e.ClickedItem;
            txtAutocompleteAddress.Text = item.description;
            using (var client = new HttpClient())
            {
                string reqUri = "https://maps.googleapis.com/maps/api/geocode/json?address=" + item.description + "&key=AIzaSyA9rHJZEGWe6rX4nAHTGXFxCubmw-F0BBw";
                HttpResponseMessage response = await client.GetAsync(reqUri);
                if (response.IsSuccessStatusCode)
                {
                    var jsonString = response.Content.ReadAsStringAsync().Result;
                    JObject json = JObject.Parse(jsonString);
                    lat = json["results"][0]["geometry"]["location"]["lat"].ToString().Replace(",", ".");
                    lng = json["results"][0]["geometry"]["location"]["lng"].ToString().Replace(",", ".");
                    webViewMap.Navigate(new Uri(session.getUrlWebView() + "location-map.php?lat=" + lat + "&lng=" + lng));
                }
            }
            listAutoCompleteAddress.Clear();
        }

        private async void searchAutocomplete()
        {
            string input = txtAutocompleteAddress.Text;
            using (var client = new HttpClient())
            {
                string reqUri = "https://maps.googleapis.com/maps/api/place/autocomplete/json?input=" + input + "&types=geocode&location=-7.252115,112.752849&radius=20000&language=id&components=country:id&strictbounds&key=AIzaSyA9rHJZEGWe6rX4nAHTGXFxCubmw-F0BBw";
                HttpResponseMessage response = await client.GetAsync(reqUri);
                if (response.IsSuccessStatusCode)
                {
                    var jsonString = response.Content.ReadAsStringAsync().Result;
                    JObject json = JObject.Parse(jsonString);
                    if (json["status"].ToString() == "OK")
                    {
                        listAutoCompleteAddress.Clear();
                        var token = JToken.Parse(jsonString)["predictions"].ToList().Count;
                        for (int i = 0; i < token; i++)
                        {
                            string description = json["predictions"][i]["description"].ToString();
                            string placeId = json["predictions"][i]["place_id"].ToString();
                            listAutoCompleteAddress.Add(new AutocompleteAddress(description, placeId));
                        }
                        lvSuggestion.ItemsSource = listAutoCompleteAddress;
                    }
                    else
                    {
                        if (txtAutocompleteAddress.Text.Length != 0)
                        {
                            listAutoCompleteAddress.Clear();
                            listAutoCompleteAddress.Add(new AutocompleteAddress("Tidak ada hasil ditemukan", ""));
                            lvSuggestion.ItemsSource = listAutoCompleteAddress;
                        }
                    }
                }
            }
        }

        private void txtAutocompleteAddressTextChanged(object sender, TextChangedEventArgs e)
        {
            if (!dispatcherTimer.IsEnabled)
            {
                dispatcherTimer.Start();
            }
            tick = 0;
        }

        public async void CrimeReportPageLoaded(object sender,RoutedEventArgs e)
        {
            userLogin = session.getUserLogin();
            using (var client = new HttpClient())
            {
                client.BaseAddress = new Uri(session.getApiURL());
                client.DefaultRequestHeaders.Accept.Clear();
                HttpResponseMessage response = await client.GetAsync("/getAllKategoriCrime");
                if (response.IsSuccessStatusCode)
                {
                    var jsonString = await response.Content.ReadAsStringAsync();
                    var responseData = response.Content.ReadAsStringAsync().Result;
                    listSetingKategoriKriminalitas = JsonConvert.DeserializeObject<List<SettingKategori>>(responseData);
                    cbJenisKejadian.ItemsSource = listSetingKategoriKriminalitas;
                    cbJenisKejadian.DisplayMemberPath = "nama_kategori";
                    cbJenisKejadian.SelectedValuePath = "id_kategori";
                }
            }
        }

        private void deleteFile(object sender, RoutedEventArgs e)
        {
            Button selectedBtn = sender as Button;
            listImage.RemoveAt(Convert.ToInt32(selectedBtn.Tag));
            stackFile.Children.Remove((UIElement)this.FindName("sp" + selectedBtn.Tag.ToString()));
            imageCount--;
            updateTxtImageCount();
        }

        public void updateTxtImageCount()
        {
            txtImageCount.Text = imageCount + " gambar terpilih(Max. 2 Gambar)";
        }

        public async void useLocation(object sender, RoutedEventArgs e)
        {
            isChosen = true;
            var location = await Geolocation.GetLastKnownLocationAsync();
            if (location == null)
            {
                location = await Geolocation.GetLocationAsync(new GeolocationRequest
                {
                    DesiredAccuracy = GeolocationAccuracy.Medium,
                    Timeout = TimeSpan.FromSeconds(30)
                }); ;
            }
            lat = location.Latitude.ToString().Replace(",", ".");
            lng = location.Longitude.ToString().Replace(",", ".");
            using (var client = new HttpClient())
            {
                string latlng = lat + "," + lng;
                string reqUri = "https://maps.googleapis.com/maps/api/geocode/json?latlng="+latlng+"&key=AIzaSyA9rHJZEGWe6rX4nAHTGXFxCubmw-F0BBw";
                HttpResponseMessage response = await client.GetAsync(reqUri);
                if (response.IsSuccessStatusCode)
                {
                    var jsonString = response.Content.ReadAsStringAsync().Result;
                    JObject json = JObject.Parse(jsonString);
                    string address = json["results"][0]["formatted_address"].ToString();
                    txtAutocompleteAddress.Text = address;
                    webViewMap.Navigate(new Uri(session.getUrlWebView() + "location-map.php?lat=" + lat + "&lng=" + lng));
                }
            }
        }

        public void goToDetail(object sender, RoutedEventArgs e)
        {
            string judulLaporan = txtJudulLaporan.Text;
            string descKejadian = txtDescKejadian.Text;
            string valueKategoriKejadian = cbJenisKejadian.SelectedValue.ToString();
            string alamatLaporan = txtAutocompleteAddress.Text;
            string displayJeniskejadian = listSetingKategoriKriminalitas[cbJenisKejadian.SelectedIndex].nama_kategori.ToString();
            string valueJenisKejadian = cbJenisKejadian.SelectedValue.ToString();
            string tglLaporan = DateTime.Now.ToString("dd/MM/yyyy");
            string waktuLaporan = DateTime.Now.ToString("HH:mm:ss");
            string namaFileGambar = listSetingKategoriKriminalitas[cbJenisKejadian.SelectedIndex].file_gambar_kategori;
            CrimeReportParams param = new CrimeReportParams(userLogin,judulLaporan, lat, lng, descKejadian, tglLaporan, waktuLaporan, alamatLaporan, displayJeniskejadian, valueJenisKejadian, listImage,namaFileGambar);
            session.setCrimeReportDetailPageParams(param);
            this.Frame.Navigate(typeof(CrimeReportDetailPage));
        }

        public async void chooseImage(object sender, RoutedEventArgs e)
        {
            if (imageCount < 3)
            {
                string contents = "";
                try
                {
                    FileData fileData = await CrossFilePicker.Current.PickFile(new string[] { ".jpg" });
                    if (fileData == null)
                    {
                        return; // user canceled file picking
                    }
                    string fileName = fileData.FileName;
                    UploadedImage imageBaru = new UploadedImage(fileData.DataArray, fileData.DataArray.Length);
                    listImage.Add(imageBaru);
                    StackPanel sp = new StackPanel();
                    sp.Orientation = Orientation.Horizontal;
                    sp.Name = "sp" + imageCount.ToString();
                    sp.Margin = new Thickness(0, 5, 0, 0);
                    TextBlock newFile = new TextBlock();
                    newFile.Text = fileData.FileName;
                    newFile.FontSize = 15;
                    newFile.Width = 250;
                    newFile.Margin = new Thickness(25, 0, 0, 0);
                    newFile.TextWrapping = TextWrapping.Wrap;
                    sp.Children.Add(newFile);
                    Button btnClose = new Button();
                    btnClose.Content = "X";
                    btnClose.Click += deleteFile;
                    btnClose.Tag = imageCount.ToString();
                    btnClose.Name = "btn" + imageCount.ToString();
                    sp.Children.Add(btnClose);
                    stackFile.Children.Add(sp);
                    imageCount++;
                    updateTxtImageCount();
                }
                catch (Exception ex)
                {
                    System.Console.WriteLine("Exception choosing file: " + ex.ToString());
                }
            }
            else
            {
                var message = new MessageDialog("Anda hanya dapat mengupload maksimal 3 gambar saja");
                await message.ShowAsync();
            }
        }
    }
}
