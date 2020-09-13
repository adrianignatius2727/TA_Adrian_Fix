﻿using System;
using Newtonsoft.Json.Linq;
using System.Linq;
using System.Net.Http;
using Windows.UI.Xaml;
using Windows.UI.Xaml.Controls;
using Windows.UI.Popups;
using System.Collections.ObjectModel;
using System.Timers;
using System.Net.Http.Headers;

// The Blank Page item template is documented at https://go.microsoft.com/fwlink/?LinkId=234238

namespace SahabatSurabaya
{
    public sealed partial class RegisterPage : Page
    {
        DispatcherTimer dispatcherTimer;
        int tick = 0;
        string lat, lng = "";
        bool isChosen = false;
        Session session = new Session();
        ObservableCollection<AutocompleteAddress> listAutoCompleteAddress = new ObservableCollection<AutocompleteAddress>();
        public RegisterPage()
        {
            this.InitializeComponent();
            dispatcherTimer = new DispatcherTimer();
            dispatcherTimer.Interval = new TimeSpan(0, 0, 0, 0, 200);
            dispatcherTimer.Tick += DispatcherTimer_Tick;
        }

        private void DispatcherTimer_Tick(object sender, object e)
        {
            tick++;
            if (tick == 2 && txtAutocompleteAddress.Text.Length!=0 && !isChosen)
            {
                searchAutocomplete();
            }
            if (isChosen && tick==2) isChosen = false;
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
                    lng = json["results"][0]["geometry"]["location"]["lng"].ToString().Replace(",", "."); ;
                }
            }
            listAutoCompleteAddress.Clear();
        }

        private void goToLogin(object sender, RoutedEventArgs e)
        {
            this.Frame.Navigate(typeof(LoginPage));

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
        

        private async void register(object sender, RoutedEventArgs e)
        {
            if(txtFullName.Text.Length!=0 && txtPassword.Password.Length!=0 && txtConfirmPassword.Password.Length!=0 && txtPhone.Text.Length != 0)
            {
                if (txtPassword.Password == txtConfirmPassword.Password)
                {
                    using (var client = new HttpClient())
                    {
                        client.BaseAddress = new Uri(session.getApiURL());
                        client.DefaultRequestHeaders.Accept.Clear();
                        client.DefaultRequestHeaders.Accept.Add(new MediaTypeWithQualityHeaderValue("application/json"));
                        MultipartFormDataContent form = new MultipartFormDataContent();
                        form.Add(new StringContent(txtFullName.Text), "nama_user");
                        form.Add(new StringContent(txtPhone.Text), "telpon_user");
                        form.Add(new StringContent(txtEmail.Text), "email_user");
                        form.Add(new StringContent(txtPassword.Password), "password_user");
                        form.Add(new StringContent(lat), "lat_user");
                        form.Add(new StringContent(lng), "lng_user");
                        form.Add(new StringContent(txtAutocompleteAddress.Text), "alamat_user");
                        HttpResponseMessage response = await client.PostAsync("user/registerUser", form);
                        if (response.IsSuccessStatusCode)
                        {
                            this.Frame.Navigate(typeof(VerifyOtpPage));
                        }
                    }
                }
                else
                {
                    var message = new MessageDialog("Confirm password tidak sesuai dengan password yang dimasukkan");
                    await message.ShowAsync();
                }
            }
            else
            {
                var message = new MessageDialog("Ada field yang masih kosong, harap lengkapi data terlebih dahulu");
                await message.ShowAsync();
            }
            
            
        }
    }
}
