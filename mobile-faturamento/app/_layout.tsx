import { DefaultTheme, ThemeProvider } from '@react-navigation/native';
import { Stack } from 'expo-router';
import { StatusBar } from 'expo-status-bar';

const navigationTheme = {
  ...DefaultTheme,
  colors: {
    ...DefaultTheme.colors,
    background: '#F5EFE4',
    card: '#F5EFE4',
    border: '#DDD0BC',
    primary: '#2563EB',
    text: '#1F2937',
    notification: '#D97706',
  },
};

export default function RootLayout() {
  return (
    <ThemeProvider value={navigationTheme}>
      <Stack
        screenOptions={{
          headerShown: false,
          contentStyle: { backgroundColor: '#F5EFE4' },
        }}
      >
        <Stack.Screen name="index" />
      </Stack>
      <StatusBar style="dark" />
    </ThemeProvider>
  );
}
