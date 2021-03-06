﻿using Com.OneSignal;
using Com.OneSignal.Abstractions;
using Microsoft.Extensions.Logging;
using SahabatSurabaya.Shared;
using System;
using System.Collections.Generic;
using System.IO;
using System.Linq;
using System.Runtime.InteropServices.WindowsRuntime;
using System.ServiceModel.Channels;
using Windows.ApplicationModel;
using Windows.ApplicationModel.Activation;
using Windows.Foundation;
using Windows.Foundation.Collections;
using Windows.UI.Popups;
using Windows.UI.Xaml;
using Windows.UI.Xaml.Controls;
using Windows.UI.Xaml.Controls.Primitives;
using Windows.UI.Xaml.Data;
using Windows.UI.Xaml.Input;
using Windows.UI.Xaml.Media;
using Windows.UI.Xaml.Navigation;
using SahabatSurabaya.Shared.Pages;

namespace SahabatSurabaya
{
    sealed partial class Application : Windows.UI.Xaml.Application
    {

        /// <summary>
        /// Initializes the singleton application object.  This is the first line of authored code
        /// executed, and as such is the logical equivalent of main() or WinMain().
        /// </summary>
        public Application()
        {
            this.InitializeComponent();
            this.Suspending += OnSuspending;

#if __ANDROID__
            OneSignal.Current.StartInit("6fd226ba-1d41-4c7b-9f8b-a973a8fd436b").HandleNotificationOpened(HandleNotificationOpened)
                             .Settings(new Dictionary<string, bool>() {
                                                        { IOSSettings.kOSSettingsKeyAutoPrompt, false },
                                                        { IOSSettings.kOSSettingsKeyInAppLaunchURL, false } })
                             .InFocusDisplaying(OSInFocusDisplayOption.Notification)
                             .EndInit();
#endif
        }

        private static void HandleNotificationOpened(OSNotificationOpenedResult result)
        {
            Session session = new Session();
            OSNotificationPayload payload = result.notification.payload;
            Dictionary<string, object> additionalData = payload.additionalData;
            if (additionalData != null)
            {
                Frame rootFrame = Windows.UI.Xaml.Window.Current.Content as Frame;
                string page = additionalData["page"].ToString();
                if (page == "1")
                {
                    int id_user_pelapor = Convert.ToInt32(additionalData["id_user_pelapor"].ToString());
                    string nama_user_pelapor = additionalData["nama_user_pelapor"].ToString();
                    string id_laporan = additionalData["id_laporan"].ToString();
                    string alamat_laporan = additionalData["alamat_laporan"].ToString();
                    string tanggal_laporan = additionalData["tanggal_laporan"].ToString();
                    string waktu_laporan = additionalData["waktu_laporan"].ToString();
                    string judul_laporan = additionalData["judul_laporan"].ToString();
                    string jenis_laporan = additionalData["jenis_laporan"].ToString();
                    string deskripsi_laporan = additionalData["deskripsi_laporan"].ToString();
                    string lat_laporan = additionalData["lat_laporan"].ToString();
                    string lng_laporan = additionalData["lng_laporan"].ToString();
                    string tag = additionalData["tag"].ToString();
                    string thumbnail_gambar = additionalData["thumbnail_gambar"].ToString();
                    int status_laporan = Convert.ToInt32(additionalData["status_laporan"].ToString());
                    int? jumlah_konfirmasi = 0;
                    if (tag == "kriminalitas"){
                        jumlah_konfirmasi = Convert.ToInt32(additionalData["jumlah_konfirmasi"].ToString());
                    }
                    else{
                        jumlah_konfirmasi = null;
                    }
                    ReportDetailPageParams param = new ReportDetailPageParams(id_user_pelapor, nama_user_pelapor, id_laporan, alamat_laporan, tanggal_laporan, waktu_laporan, judul_laporan, jenis_laporan, deskripsi_laporan, lat_laporan, lng_laporan, tag, thumbnail_gambar, status_laporan,jumlah_konfirmasi);
                    session.setReportDetailPageParams(param);
                    rootFrame.Navigate(typeof(ReportDetailPage));
                }
            }
            
            
        }

        /// <summary>
        /// Invoked when the application is launched normally by the end user.  Other entry points
        /// will be used such as when the application is launched to open a specific file.
        /// </summary>
        /// <param name="e">Details about the launch request and process.</param>
        protected override void OnLaunched(LaunchActivatedEventArgs e)
        {
#if DEBUG
            if (System.Diagnostics.Debugger.IsAttached)
            {
                // this.DebugSettings.EnableFrameRateCounter = true;
            }
#endif
            Frame rootFrame = Windows.UI.Xaml.Window.Current.Content as Frame;

            // Do not repeat app initialization when the Window already has content,
            // just ensure that the window is active
            if (rootFrame == null)
            {
                // Create a Frame to act as the navigation context and navigate to the first page
                rootFrame = new Frame();

                rootFrame.NavigationFailed += OnNavigationFailed;

                if (e.PreviousExecutionState == ApplicationExecutionState.Terminated)
                {
                    //TODO: Load state from previously suspended application
                }

                // Place the frame in the current Window
                Windows.UI.Xaml.Window.Current.Content = rootFrame;
            }

            if (e.PrelaunchActivated == false)
            {
                if (rootFrame.Content == null)
                {
                    // When the navigation stack isn't restored navigate to the first page,
                    // configuring the new page by passing required information as a navigation
                    // parameter
                    rootFrame.Navigate(typeof(Splashscreen), e.Arguments);
                }
                // Ensure the current window is active
                Windows.UI.Xaml.Window.Current.Activate();
            }
        }

        /// <summary>
        /// Invoked when Navigation to a certain page fails
        /// </summary>
        /// <param name="sender">The Frame which failed navigation</param>
        /// <param name="e">Details about the navigation failure</param>
        void OnNavigationFailed(object sender, NavigationFailedEventArgs e)
        {
            throw new Exception($"Failed to load {e.SourcePageType.FullName}: {e.Exception}");
        }

        /// <summary>
        /// Invoked when application execution is being suspended.  Application state is saved
        /// without knowing whether the application will be terminated or resumed with the contents
        /// of memory still intact.
        /// </summary>
        /// <param name="sender">The source of the suspend request.</param>
        /// <param name="e">Details about the suspend request.</param>
        private void OnSuspending(object sender, SuspendingEventArgs e)
        {
            var deferral = e.SuspendingOperation.GetDeferral();
            //TODO: Save application state and stop any background activity
            deferral.Complete();
        }
    }
}
