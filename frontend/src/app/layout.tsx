import { Nav } from './clientUtils'
import { ThemeProvider } from './clientUtils'
import './globals.css'

export default function RootLayout({
  children,
}: {
  children: React.ReactNode
}) {

  return (
    <html lang="en">
      {/*
        <head /> will contain the components returned by the nearest parent
        head.tsx. Find out more at https://beta.nextjs.org/docs/api-reference/file-conventions/head
      */}
      <head />
      <body>
        <ThemeProvider attribute='class' enableSystem={true}>
          <div className="flex flex-col h-screen">
            <Nav />
            {children}
          </div>
        </ThemeProvider>
      </body>
    </html>
  )
}
